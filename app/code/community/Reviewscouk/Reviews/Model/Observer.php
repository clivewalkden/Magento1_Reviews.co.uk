<?php

class Reviewscouk_Reviews_Model_Observer
{
	private $api_url = 'https://api.reviews.co.uk';

	/**
	 * This method is called after an order is placed
	 */
	public function dispatch_notification(Varien_Event_Observer $observer)
	{
		try
		{
			$shipment         = $observer->getEvent()->getShipment();
			$order            = $shipment->getOrder();
			$magento_store_id = $order->getStoreId();
			if ($this->getRegion($magento_store_id) == 'US')
			{
				$this->api_url = 'http://api.review.io';
			}

			if ($this->getStoreId($magento_store_id) && $this->getApiKey($magento_store_id) && Mage::getStoreConfig('reviewscouk_reviews_settings/general/reviews_merchant_enabled', $magento_store_id))
			{
				$order_params             = array();
				$order_params['name']     = $order->getCustomerName();
				$order_params['store']    = $this->getStoreId($magento_store_id);
				$order_params['email']    = $order->getCustomerEmail();
				$order_params['order_id'] = $order->getRealOrderId();
				$order_params['api_key']  = $this->getApiKey($magento_store_id);

				$product_url_string = $this->api_url . '/merchant/invitation';
				$ch                 = curl_init($product_url_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'store:' . $this->getStoreId($magento_store_id),
					'apikey:' . $this->getApiKey($magento_store_id)
				));
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $order_params);
				curl_exec($ch);

				curl_close($ch);
			}
			if ($this->getStoreId($magento_store_id) && $this->getApiKey($magento_store_id) && Mage::getStoreConfig('reviewscouk_reviews_settings/general/reviews_products_enabled', $magento_store_id))
			{
				$items = $order->getAllVisibleItems();
				foreach ($items as $item)
				{
					// Load the product
					$item = Mage::getModel('catalog/product')->load($item->getProductId());

					// If product is part of a grouped product, use the grouped product details.
					$enabled = Mage::getStoreConfig('reviewscouk_reviews_settings/advanced/reviews_use_group_product_sku', Mage::app()->getStore());

					if ($enabled)
					{
						// If product is part of a grouped product, use the grouped product details.
						$parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($item->getId());
						if (!empty($parentIds))
						{
							$item = Mage::getModel('catalog/product')->load($parentIds[0]);
						}
					}

					// Prepare data
					$image = Mage::getModel('catalog/product_media_config')->getMediaUrl($item->getThumbnail());
					$p[]   = array(
						'image'   => $image,
						'id'      => $item->getProductId(),
						'sku'     => $item->getSku(),
						'name'    => $item->getName(),
						'pageUrl' => $item->getProductUrl()
					);
				}

				$post_params['order_id'] = $order->getRealOrderId();
				$post_params['email']    = $order->getCustomerEmail();
				$post_params['name']     = $order->getCustomerName();
				$post_params['products'] = json_encode($p);

				// Send product request
				$product_url_string = $this->api_url . '/product/invitation';
				$ch                 = curl_init($product_url_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'store:' . $this->getStoreId($magento_store_id),
					'apikey:' . $this->getApiKey($magento_store_id)
				));
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
				curl_exec($ch);

				curl_close($ch);
			}

		} catch (Exception $e)
		{

			if ($this->debugEnabled())
			{
				echo "Oops! Something went wrong";
				exit();
			}
		}

	}

	/**
	 * Method for calling api url using curl
	 */
	function apiCall($url)
	{
		try
		{
			$curl = new Varien_Http_Adapter_Curl();
			$curl->setConfig(array(
				'timeout'   => 15,
				'useragent' => $this->getuseragent($_SERVER['HTTP_HOST'])
			));
			$curl->write(Zend_Http_Client::GET, $url, '1.0');

			$resp = $curl->read();
			$curl->close();
			if ($this->debugEnabled())
			{
				Mage::log('API Request: ' . $url, null, 'reviews.log');
				Mage::log('API Response: ' . $resp, null, 'reviews.log');
			}
			return $resp;
		} catch (Exception $e)
		{
			Mage::log('Error: ' . print_r($e), NULL, 'reviews.log');
		}
	}

	/**
	 * Generate a user agent string based on http_host
	 */
	function getUserAgent($url)
	{
		$data = array(
			'url'   => $url,
			'time'  => (strtotime("now") >> 8),
			'users' => array()
		);
		if (function_exists("mcrypt_create_iv"))
		{
			$iv        = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
			$useragent = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $url, (json_encode($data)), MCRYPT_MODE_CBC, $iv);
		}
		else
		{
			$iv        = "fb";
			$useragent = json_encode($data);
		}
		return "reviewscouk bot #" . base64_encode($useragent . "///" . $iv);
	}

	/*
	 * Region getter
	 */
	function getRegion($magentoStore)
	{
		return Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_region', $magentoStore);
	}

	/**
	 * Retrieve the API key as set in settings
	 */
	function getApiKey($magentoStore)
	{
		$apikey = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_api_key', $magentoStore);
		if (!empty($apikey))
		{
			return $apikey;
		}
		return false;
	}

	function getStoreId($magentoStore)
	{
		$store_id = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id', $magentoStore);
		if (!empty($store_id))
		{
			return $store_id;
		}
		return false;
	}

	/**
	 * Check if debug is enabled
	 */
	function debugEnabled()
	{
		return Mage::getStoreConfig('reviewscouk_reviews_settings/advanced/debug');
	}
}