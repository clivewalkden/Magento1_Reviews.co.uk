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

    public function fetchProductReviews($page=1){
        $storeId = Mage::app()->getStore()->getId();
        if (!$storeId) {
                $storeId = 1;	
        }

        $store_id = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id',$storeId);

        if(empty($store_id)){
          throw new Exception('Please Configure API Credentials'); 
        }

        try {
          $url = "https://api.reviews.co.uk/product/review?store=".$store_id."&per_page=100";
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
          $data = curl_exec($ch);
        }
        catch(Exception $e){
          throw new Exception('Could not connect to Reviews.co.uk API');
        }

        try {  
          $response = json_decode($data);
        }
        catch(Exception $e){
          throw new Exception('Problem Parsing Data');
        }

        if(is_object($response)){
          return $response;
        }
        else
        {
          throw new Exception('Could not communicate to Reviews.co.uk API');
        }
    }

    public function syncAction(){
    
        try { 
          $fetch = $this->fetchProductReviews();

          $lastPage = $fetch->reviews->last_page;

          for($i=1;$i<=$lastPage;$i++){
            $fetch = $this->fetchProductReviews($i);

            foreach($fetch->reviews->data as $review){
                $product = Mage::getModel('catalog/product');
                $productId = $product->getIdBySku($r[0]);

                echo '<p>'.json_encode($review).'</p>';
        	
        	/*$review = Mage::getModel('review/review');
        	$review->setEntityPkValue($productId);
        	$review->setStatusId(1);
        	$review->setTitle($r[3]);
        	$review->setDetail($r[4]);
        	$review->setEntityId(1);
        	$review->setStoreId($storeId);
        	$review->setStatusId(1);
        	$review->setCustomerId(null);
        	$review->setNickname($r[1]);
        	$review->setReviewId($review->getId());
        	$review->setStores(array(0, $storeId));
                $review->save();*/
           
            }
          }

          echo 'Done'; 
        }
        catch(Exception $e){
          die($e->getMessage());
        }

         
       /* 
        $imported =0;
        $r = fgetcsv($csv);

        while (($r = fgetcsv($csv))!==false) {
          $data = $r;
          print_r($data);die();
        	$product = Mage::getModel('catalog/product');
        	$productId = $product->getIdBySku($r[0]);
        	
        	$review = Mage::getModel('review/review');
        	$review->setEntityPkValue($productId);
        	$review->setStatusId(1);
        	$review->setTitle($r[3]);
        	$review->setDetail($r[4]);
        	$review->setEntityId(1);
        	$review->setStoreId($storeId);
        	$review->setStatusId(1);
        	$review->setCustomerId(null);
        	$review->setNickname($r[1]);
        	$review->setReviewId($review->getId());
        	$review->setStores(array(0, $storeId));
        	$review->save();
        	
			$rr = 1;
		print_r($r[7]);
		
			$ratings = json_decode(stripslashes($r[7]));
			
			$matched = false;
		print_r($ratings);

	
        	foreach ($ratings as $label=>$value) {
	        		$rating = Mage::getModel('rating/rating')->getCollection()
		        		->addFieldToFilter('rating_code', $label)->load()
		        		->setPageSize(1)
		        		->load()
		        		->getFirstItem();
        			
				

	        		if ($rating->getId()) {
		        		$ratingOption = Mage::getModel('rating/rating_option')->getCollection()
				        	//->addFieldToSelect('option_id')
				        	->addFieldToFilter('rating_id', $rating->getId())
				        	->addFieldToFilter('value', $value)->load()
				        	->setPageSize(1)
				        	->load()
				        	->getFirstItem();
			        	//echo "add ".$rating->getId." ".$value." <br/>";
		        				    
					if ($ratingOption->getId()){
			        		$rating = Mage::getModel('rating/rating')
				        		->setRatingId($rating->getId())
				        		->setReviewId($review->getId())
				        		->addOptionVote($ratingOption->getId(), $productId);
	        			
			        		$matched = true;
					} 
	        		}
        	} 
        	
        	if (!$matched) {
	        	$rr = 1;
        		while ($rr < 4) {
	        		$ratingOption = Mage::getModel('rating/rating_option')->getCollection()
			        	//->addFieldToSelect('option_id')
			        	->addFieldToFilter('rating_id', $rr)
			        	->addFieldToFilter('value',$r[5])->load()
			        	->setPageSize(1)
			        	->load()
			        	->getFirstItem();
		        	
				if ($ratingOption->getId()) {		    
		        		$rating = Mage::getModel('rating/rating')
			        		->setRatingId($rr)
			        		->setReviewId($review->getId())
			        		->addOptionVote($ratingOption->getId(), $productId);
		        	}
		        	$rr++;	
	        	} 
        	}

        	$review->aggregate();
        	        	
        	$imported++;
        }*/
    }
 
    public function exportAction()
	{
        $this->loadLayout()
            ->_setActiveMenu('reviewstab')
			->_title($this->__('Export Magento Reviews'));
		 
        $this->renderLayout();
	}

	public function doexportAction(){
		header('Content-Disposition: attachment; filename="magento_export.json"');
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');

		$review_table = $resource->getTableName('review');
		$review_detail_table = $resource->getTableName('review_detail');
		$rating_table = $resource->getTableName('rating');
		$rating_option_vote_table = $resource->getTableName('rating_option_vote');
		$catalog_product_entity_table = $resource->getTableName('catalog_product_entity');

		$query = 'select r.review_id,p.sku as product,rd.title,rd.nickname as reviewer,rd.detail as review,r.created_at as date from '.$review_table.' r INNER JOIN '.$review_detail_table.' rd ON r.review_id = rd.review_id LEFT JOIN '.$catalog_product_entity_table.' p ON p.entity_id = r.entity_pk_value  WHERE r.status_id=1';
		$results = $readConnection->fetchAll($query);

		foreach($results as &$result){
			$vote_query = 'select r.rating_code,rv.value from '.$rating_option_vote_table.' rv INNER JOIN '.$rating_table.' r ON rv.rating_id = r.rating_id where rv.review_id = '.$result['review_id'];
			$votes = $readConnection->fetchAll($vote_query);
			$ratings = array();
			foreach($votes as $vote){
				$ratings[$vote['rating_code']] = $vote['value'];
			}
			$result['import_ref'] = 'MAGENTO-'.$result['review_id'];
			$result['ratings'] = $ratings;
		}

		echo $this->pretty_json(json_encode($results));
	}

	function pretty_json($json) {
	 
		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '  ';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;
	 
		for ($i=0; $i<=$strLen; $i++) {
	 
			// Grab the next character in the string.
			$char = substr($json, $i, 1);
	 
			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;
	 
			// If this character is the end of an element, 
			// output a new line and indent the next line.
			} else if(($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++) {
					$result .= $indentStr;
				}
			}
	 
			// Add the character to the result string.
			$result .= $char;
	 
			// If the last character was the beginning of an element, 
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos ++;
				}
	 
				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}
	 
			$prevChar = $char;
		}
	 
		return $result;
	}
}
