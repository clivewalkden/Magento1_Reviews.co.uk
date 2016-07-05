<?php
class Reviewscouk_Reviews_Model_Observer
{
	public function order_shipped(Varien_Event_Observer $observer){
		$shipment = $observer->getEvent()->getShipment();
		$order = $shipment->getOrder();
		$this->dispatch_notification($order);
	}

	protected function getApiDomain($magento_store_id=null){
		return $this->getRegion($magento_store_id) == 'US'? 'api.reviews.io' : 'api.reviews.co.uk';
	}

	protected function apiPost($url, $data, $magento_store_id=null){
		if($magento_store_id == null){
			$magento_store_id = Mage::app()->getStore();
		}

		$api_url = 'https://'.$this->getApiDomain($magento_store_id).'/'.$url;
		$ch = curl_init($api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'store: '.$this->getStoreId($magento_store_id),
			'apikey: '.$this->getApiKey($magento_store_id),
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	public function dispatch_notification($order)
	{
		try
		{
			$magento_store_id = $order->getStoreId();

			if ($this->getStoreId($magento_store_id) && $this->getApiKey($magento_store_id) && Mage::getStoreConfig('reviewscouk_reviews_settings/general/reviews_merchant_enabled', $magento_store_id))
			{
				$merchantResponse = $this->apiPost('merchant/invitation', array(
					'source' => 'magento',
					'name' => $order->getCustomerName(),
					'email' => $order->getCustomerEmail(),
					'order_id' => $order->getRealOrderId(),
				), $magento_store_id);
			}

			if ($this->getStoreId($magento_store_id) && $this->getApiKey($magento_store_id) && Mage::getStoreConfig('reviewscouk_reviews_settings/general/reviews_products_enabled', $magento_store_id))
			{
				$items = $order->getAllVisibleItems();
				foreach ($items as $item)
				{
					$item = Mage::getModel('catalog/product')->load($item->getProductId());

					if (Mage::getStoreConfig('reviewscouk_reviews_settings/advanced/reviews_use_group_product_sku', Mage::app()->getStore()))
					{
						// If product is part of a grouped product, use the grouped product details.
						$parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($item->getId());
						if (!empty($parentIds))
						{
							$item = Mage::getModel('catalog/product')->load($parentIds[0]);
						}
					}

					$p[]   = array(
						'image'   => Mage::getModel('catalog/product_media_config')->getMediaUrl($item->getThumbnail()),
						'id'      => $item->getProductId(),
						'sku'     => $item->getSku(),
						'name'    => $item->getName(),
						'pageUrl' => $item->getProductUrl()
					);
				}

				$productResponse = $this->apiPost('product/invitation', array(
					'source' => 'magento',
					'name' => $order->getCustomerName(),
					'email' => $order->getCustomerEmail(),
					'order_id' => $order->getRealOrderId(),
					'products' => $p
				), $magento_store_id);
			}
		}
		catch (Exception $e)
		{
		}
	}

	function getRegion($magentoStore)
	{
		return Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_region', $magentoStore);
	}

	function getApiKey($magentoStore)
	{
		return Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_api_key', $magentoStore);
	}

	function getStoreId($magentoStore)
	{
		return Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id', $magentoStore);
	}

	function createFeed()
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => Mage::getBaseUrl().'reviews/index/feed',
			CURLOPT_USERAGENT      => 'Cron Curl Request',
			CURLOPT_POST           => 1,
		));

		$resp = curl_exec($curl);
		curl_close($curl);
	}

	public function after_save(){
		$this->apiPost('integration/set-feed', array(
			'url' => Mage::getBaseUrl().'reviews/index/feed',
			'format' => 'xml'
		));

		$this->apiPost('integration/app-installed', array(
			'platform' => 'magento',
			'url' => isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : ''
		));
	}
}
