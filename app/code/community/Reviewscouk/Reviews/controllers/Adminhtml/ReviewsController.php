<?php

class Reviewscouk_Reviews_Adminhtml_ReviewsController extends Mage_Adminhtml_Controller_Action
{
	public function dashboardAction()
	{
		$this->loadLayout()
			 ->_setActiveMenu('reviewstab')
			 ->_title($this->__('Dashboard'));

		$this->renderLayout();
	}

	public function fetchProductReviews($page = 1)
	{

		// Api Key
		$apikey = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_api_key', Mage::app()->getStore());

		// Get Region
		$region = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_region', Mage::app()->getStore());

		// Get store
		$storeName = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id', Mage::app()->getStore());

		if (empty($storeName))
		{
			throw new Exception('Please Configure API Credentials');
		}

		try
		{
			$url = Mage::helper('reviewshelper')->getReviewsUrl('api')."/product/reviews/all?store=" . $storeName . "&apikey=" . $apikey . "&page=" . $page;

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			$data = curl_exec($ch);
		} catch (Exception $e)
		{
			Mage::getSingleton('core/session')->addSuccess('Cannot connect to Reviews API');
			$this->_redirectUrl('/index.php/admin');
		}

		try
		{
			$response = json_decode($data);
		} catch (Exception $e)
		{
			throw new Exception('Problem Parsing Data');
		}

		if (is_object($response))
		{
			return $response;
		}
		else
		{
			throw new Exception('Could not communicate to Reviews.co.uk API');
		}
	}

	/*
	 * Depreciated: Sync Reviews from Reviews.co.uk into Magento Default Review System
	 * Currently Not Accessible to Avoid Confusion - Not Really Necessary with Static Widget Available
	 */
	public function syncAction()
	{
		// Getting the Store ID
		$storeId = Mage::app()->getStore()->getId();

                $storeIds = array(0);
                foreach (Mage::app()->getWebsites() as $website) {
                    foreach ($website->getGroups() as $group) {
                        $stores = $group->getStores();
                        foreach ($stores as $store) {
                            //$store is a store object
                            $storeIds[] = $store->store_id;
                        }
                    }
                }

		if (!$storeId) $storeId = 1;

		// Import Counter
		$imported = 0;
		$skipped = 0;
		$total = 0;

		// Table Prefix
		$prefix = Mage::getConfig()->getTablePrefix();

		try
		{
			$fetch = $this->fetchProductReviews();

			for ($i = 0; $i <= $fetch->total_pages; $i++)
			{
				$fetch = $this->fetchProductReviews($i);

				foreach ($fetch->reviews as $row)
				{

					$skipped++;

					$comment     = $row->review;

					$connection  = Mage::getSingleton('core/resource')->getConnection('core_read');
					$sql         = "Select * from " . $prefix . "review_detail WHERE detail = ? ";
					$reviewExist = $connection->fetchRow($sql, $comment);

					$review      = (count($reviewExist) == 0) ? Mage::getModel('review/review') : Mage::getModel('review/review')->load($reviewExist['review_id']);

					$product_id = Mage::getModel("catalog/product")->getIdBySku($row->sku);

					// Only Importing if the product exist on magento side
					if ($product_id)
					{
						$imported++;

						$review->setEntityPkValue($product_id);
						$review->setStatusId(1);
						$review->setTitle(substr($comment, 0, 50));
						$review->setDetail($comment);
						$review->setEntityId(1);
						$review->setStoreId($storeId);
						$review->setStatusId(1);
						$review->setCustomerId(null);
						$review->setNickname($row->reviewer->first_name . ' ' . $row->reviewer->last_name);
						$review->setReviewId($review->getId());
						$review->setStores($storeIds);
						$review->save();

						// If the user has provided ratings then we need to add some data to ratings table.
						if (count($row->ratings) > 0)
						{
							$ratings = $row->ratings;

							foreach ($ratings as $label => $value)
							{
								$this->sortRatings($value->rating_text,$value->rating, $product_id, $connection, $prefix, $review);
							}

							$review->aggregate();
						}
						else
						{
							$this->sortRatings('Rating', $row->rating,$product_id, $connection, $prefix, $review);

							$review->aggregate();
						}
					}
				}
			}

			$skipped = $skipped - $imported;
			$message = " Total number of reviews imported or updated were ".$imported .", Number of reviews skipped were ".$skipped;
			Mage::getSingleton('core/session')->addSuccess($message);
			$this->_redirectUrl('/index.php/admin');

		} catch (Exception $e)
		{
			die($e->getMessage());
		}
	}

	private function sortRatings($ratingText, $ratingNumber, $product_id, $connection, $prefix, $review)
	{
		$rating = Mage::getModel('rating/rating')->getCollection()
					  ->addFieldToFilter('rating_code', $ratingText)->load()
					  ->setPageSize(1)
					  ->load()
					  ->getFirstItem();

		// Sometimes there might be a option that does not exist in Magento so will be
		// Creating a new record in rating, rating_options table,
		if (!$rating->getId())
		{
			$rating = $this->createNewRatings($ratingText);
		}

		if ($rating->getId())
		{
			$ratingOption = Mage::getModel('rating/rating_option')->getCollection()
								->addFieldToFilter('rating_id', $rating->getId())
								->addFieldToFilter('value', $ratingNumber)->load()
								->setPageSize(1)
								->load()
								->getFirstItem();

			// This is just to be safe so for some reason if the rating options doesn't exist then
			// we will create a new one
			if (!$ratingOption->getId())
			{
				$this->createRatingsOptions($rating->getId());

				$ratingOption = Mage::getModel('rating/rating_option')->getCollection()
									->addFieldToFilter('rating_id', $rating->getId())
									->addFieldToFilter('value', $ratingNumber)->load()
									->setPageSize(1)
									->load()
									->getFirstItem();
			}

			if ($ratingOption->getId())
			{
				// There was a problem with duplicate vote for a reviews
				// So we are checking using the review ID and Rating Id (Both of them together makes a primary key) and only create
				// a new row if both of them doesn't exist.
				$sql       = "Select * from " . $prefix . "rating_option_vote WHERE rating_id ='" . $rating->getId() . "' AND review_id = '" . $review->getId() . "'";
				$voteExist = $connection->fetchRow($sql);
				if(!$voteExist)
				{
					$ratingVote = Mage::getModel('rating/rating');
					$ratingVote->setRatingId($rating->getId())
							   ->setReviewId($review->getId())
							   ->addOptionVote($ratingOption->getId(), $product_id);
				}
			}
		}
	}

	/**
	 * Creates New Ratings Options if they dont exist
	 * @param $ratingId
	 */
	private function createRatingsOptions($ratingId)
	{
		for ($i = 1; $i <= 5; $i++)
		{
			$options = Mage::getModel('rating/rating_option');
			$options->setRatingId($ratingId);
			$options->setCode($i);
			$options->setValue($i);
			$options->setPosition($i);
			$options->save();
		}

	}

	/**
	 * Create new Ratings
	 */
	private function createNewRatings($ratingsName)
	{
		$newRatings = Mage::getModel('rating/rating');
		$newRatings->setEntityId(1);
		$newRatings->setRatingCode($ratingsName);
		$newRatings->save();

		return $newRatings;
	}

	public function exportAction()
	{
		$this->loadLayout()
			 ->_setActiveMenu('reviewstab')
			 ->_title($this->__('Export Magento Reviews'));

		$this->renderLayout();
	}

	public function boosterAction()
	{
		$this->loadLayout()
			 ->_setActiveMenu('reviewstab')
			 ->_title($this->__('Review Booster Download'));

        $booster = array(array('name','email','order_id','product_id','product_name','date_created','date_updated', 'status', 'store_id', 'store_name'));

        $time = time(); // Now
        $from = date('Y-m-d H:i:s', $time - (60*60*24*30*3)); // 3 Months
        $to = date('Y-m-d H:i:s', $time);

        $orders = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*')
//        ->addAttributeToFilter('status', array('eq'=>'complete'))
        ->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to))->load();

        foreach($orders as $order){
            $orderItems = $order->getItemsCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('product_type', array('eq'=>'simple'))
            ->load();

            foreach($orderItems as $Item)
            {
                $Item = Mage::getModel('catalog/product')->setStoreId($Item->getStoreId())->load($Item->getProductId());
                if ($Item->getId())
                {
                    $booster[] = array($order->customer_email, $order->customer_firstname.' '.$order->customer_lastname, $order->entity_id, $Item->getSku(), $Item->getName(), $order->created_at, $order->updated_at, $order->status, $order->store_id, $order->store_name);
                }
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="booster.csv"');

        echo $this->generateCsv($booster);
	}

	public function doexportAction()
	{
		header('Content-Disposition: attachment; filename="magento_export.json"');
		$resource       = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');

		$review_table                 = $resource->getTableName('review');
		$review_detail_table          = $resource->getTableName('review_detail');
		$rating_table                 = $resource->getTableName('rating');
		$rating_option_vote_table     = $resource->getTableName('rating_option_vote');
		$catalog_product_entity_table = $resource->getTableName('catalog_product_entity');

		$query   = 'select r.review_id,p.sku as product,rd.title,rd.nickname as reviewer,rd.detail as review,r.created_at as date from ' . $review_table . ' r INNER JOIN ' . $review_detail_table . ' rd ON r.review_id = rd.review_id LEFT JOIN ' . $catalog_product_entity_table . ' p ON p.entity_id = r.entity_pk_value  WHERE r.status_id=1';
		$results = $readConnection->fetchAll($query);

		foreach ($results as &$result)
		{
			$vote_query = 'select r.rating_code,rv.value from ' . $rating_option_vote_table . ' rv INNER JOIN ' . $rating_table . ' r ON rv.rating_id = r.rating_id where rv.review_id = ' . $result['review_id'];
			$votes      = $readConnection->fetchAll($vote_query);
			$ratings    = array();
			foreach ($votes as $vote)
			{
				$ratings[$vote['rating_code']] = $vote['value'];
			}
			$result['import_ref'] = 'MAGENTO-' . $result['review_id'];
			$result['ratings']    = $ratings;
		}

		echo $this->pretty_json(json_encode($results));
	}

	function pretty_json($json)
	{

		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '  ';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;

		for ($i = 0; $i <= $strLen; $i++)
		{

			// Grab the next character in the string.
			$char = substr($json, $i, 1);

			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\')
			{
				$outOfQuotes = !$outOfQuotes;

				// If this character is the end of an element,
				// output a new line and indent the next line.
			}
			else if (($char == '}' || $char == ']') && $outOfQuotes)
			{
				$result .= $newLine;
				$pos--;
				for ($j = 0; $j < $pos; $j++)
				{
					$result .= $indentStr;
				}
			}

			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element,
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes)
			{
				$result .= $newLine;
				if ($char == '{' || $char == '[')
				{
					$pos++;
				}

				for ($j = 0; $j < $pos; $j++)
				{
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
	}

    protected function generateCsv($data, $delimiter = ',', $enclosure = '"') {
       $handle = fopen('php://temp', 'r+');
       foreach ($data as $line) {
               fputcsv($handle, $line, $delimiter, $enclosure);
       }
       rewind($handle);
       while (!feof($handle)) {
               $contents .= fread($handle, 8192);
       }
       fclose($handle);
       return $contents;
    }
}
