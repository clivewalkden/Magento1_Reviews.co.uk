<?php
class Reviewscouk_Reviews_Helper_Data extends Mage_Core_Helper_Abstract {

    public function autoRichSnippet(){
        $merchant_enabled  = Mage::getStoreConfig('reviewscouk_reviews_settings/rich_snippet/rich_snippet_enabled', Mage::app()->getStore());
        $product_enabled  = Mage::getStoreConfig('reviewscouk_reviews_settings/rich_snippet/product_rich_snippet_enabled', Mage::app()->getStore());

        $current_product = Mage::registry('current_product');

        if($current_product && $product_enabled){
            $sku = $this->getProductSkus($current_product);
            return $this->getRichSnippet($sku);
        }
        elseif($merchant_enabled){
            return $this->getRichSnippet();
        }
        return '';
    }

    public function getRichSnippet($sku=null){
        if(isset($sku) && is_array($sku)){
            $sku = implode(';',$sku);
        }

        $cache = Mage::app()->getCache();

		$apikey = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_api_key', Mage::app()->getStore());
		$region = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_region', Mage::app()->getStore());
		$storeName = Mage::getStoreConfig('reviewscouk_reviews_settings/api/reviews_store_id', Mage::app()->getStore());
        $url = $region == 'us'? 'https://widget.reviews.io/rich-snippet/dist.js' : 'https://widget.reviews.co.uk/rich-snippet/dist.js';

        $output = '<script src="'.$url.'"></script>';
        $output .= '<script>richSnippet({ store: "'.$storeName.'", sku:"'.$sku.'" })</script>';

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
