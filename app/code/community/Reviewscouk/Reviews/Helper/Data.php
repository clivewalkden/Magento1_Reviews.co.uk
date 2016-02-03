<?php
class Reviewscouk_Reviews_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getRichSnippet(){

        $sku = '';
        if(Mage::registry('current_product')){
            $sku = $this->getProductSkus(Mage::registry('current_product'));
        }

        if(is_array($sku)){
            $sku = implode(';',$sku);
        }

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


    /*
     * Product Parameter: Mage::registry('current_product')
     */
    public function getProductSkus($product){
        $sku      = $product->getSku();
        $type = $product->getTypeID();

        $productSkus = array($sku);

        if($type == 'configurable'){
            $usedProducts = $product->getTypeInstance() ->getUsedProducts();
            foreach($usedProducts as $usedProduct){
                $productSkus[] = $usedProduct->getSku();
            }
        }

        if($type == 'grouped'){
            $usedProducts = $product->getTypeInstance()->getAssociatedProducts();
            foreach($usedProducts as $usedProduct){
                $productSkus[] = $usedProduct->getSku();
            }
        }

        return $productSkus;
    }

    protected function getWidgetURL(){
        $region   = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_region', Mage::app()->getStore());
        $api_url = 'widget.reviews.co.uk';
        if ($region == 'US') $api_url = 'widget.review.io';
        return $api_url;
    }

    protected function getWidgetColor(){
        $colour  = Mage::getStoreConfig('reviewscouk_reviews_settings/widget/product_widget_colour', Mage::app()->getStore());
        // people will sometimes put hash and sometimes they will forgot so we need to check for this error
        if(strpos($colour,'#') === FALSE) $colour = '#'.$colour;
        // checking to see if we hare a valid colour. If not then we change it to reviews default hex colour
        if(!preg_match('/^#[a-f0-9]{6}$/i', $colour)) $colour = '#5db11f';
        return $colour;
    }

    public function getProductWidget(){
        $store_id = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id', Mage::app()->getStore());
        $api_url = $this->getWidgetURL();
        $colour = $this->getWidgetColor();

        $productSkus = array();
        if(Mage::registry('current_product'))
        {
            $productSkus = Mage::helper('reviewshelper')->getProductSkus(Mage::registry('current_product'));
        }

        ob_start();
        ?>
            <script src="https://<?php echo $api_url ?>/product/dist.js"></script>
            <div id="widget"></div>
            <script type="text/javascript">
                productWidget("widget", {
                    store: '<?php echo $store_id; ?>',
                    sku: '<?php echo implode(';',$productSkus); ?>', // Multiple SKU"s Seperated by Semi-Colons
                    primaryClr: "<?php echo $colour; ?>",
                    neutralClr: "#EBEBEB",
                    buttonClr: "#EEE",
                    textClr: "#333",
                    tabClr: "#eee",
                    questions: true,
                    showTabs: true,
                    ratingStars: false,
                    showAvatars: true
                });
            </script>
        <?php

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
