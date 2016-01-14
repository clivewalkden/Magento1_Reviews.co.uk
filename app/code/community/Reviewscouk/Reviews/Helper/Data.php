<?php
class Reviewscouk_Reviews_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getRichSnippet($sku=''){
        $cache = Mage::app()->getCache();

		$apikey = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_api_key', Mage::app()->getStore());
		$region = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_region', Mage::app()->getStore());
		$storeName = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id', Mage::app()->getStore());
        $url = $region == 'us'? 'http://dash.reviews.io/external/rich-snippet/'.$storeName : 'http://dash.reviews.co.uk/external/rich-snippet/'.$storeName;

        if(!empty($sku)){
            $url = $region == 'us'? 'http://dash.reviews.io/external/rich-snippet/'.$storeName.'?sku='.$sku : 'http://dash.reviews.co.uk/external/rich-snippet/'.$storeName.'?sku='.$sku;
        }

        $output = '';

        try {
            $cacheKey = 'rich_snippet_'.$sku;
            $output = $cache->load($cacheKey);
            if(!$output){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec ($ch);
                curl_close ($ch);

                $cache->save($output, $cacheKey, array("reviews_cache"), 60*60);
            }
        }
        catch(Exception $e){
        }

        return $output;
    }
}
