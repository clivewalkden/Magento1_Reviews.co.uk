<?php
class Reviewscouk_Reviews_Helper_Data extends Mage_Core_Helper_Abstract {

    /*
     * Get Reviews Config Item
     */
    public function config($config, $store=null){
        if(!$store){
            $store = Mage::app()->getStore();
        }
        return Mage::getStoreConfig('reviewscouk_reviews_settings/'.$config, $store);
    }

    /*
     * Get Store Name from Config
     */
    public function getStoreName(){
		return $this->config('api/reviews_store_id');
    }

    /*
     * Get API Key From Config
     */
    public function getApiKey(){
		return $this->config('api/reviews_api_key');
    }

    /*
     * Are Invitations Enabled
     */
    public function areInvitationsEnabled(){
        return $this->config('general/reviews_invitations_enabled', $magento_store_id);
    }

    /*
     * For a Product get array of all possible skus that reviews may be under.
     * Product Parameter: Mage::registry('current_product')
     */
    public function getProductSkus($product){
        $sku = $product->getSku();

    	// In Catalog > Attributes > Manager Attributes if sku is not set to 'Used in Product Listing' then SKU may not be available, so reload product in this case.
        // Its probably more optimal to enable the option above
        if(empty($sku)){
    		$product = Mage::getModel('catalog/product')->load($product->getId());
    		$sku = $product->getSku();
    	}

        $type = $product->getTypeID();

        $productSkus = array($sku);

        if($type == 'configurable'){
            $usedProducts = $product->getTypeInstance()->getUsedProducts();
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

    /*
     * Get Rating Snippet Code for product
     */
    public function getRatingSnippet($product){
        $skus = $this->getProductSkus($product);

        if(isset($skus) && is_array($skus)){
            $skus = implode(';',$skus);
        }

        $output = '<div class="ruk_rating_snippet" data-sku="'.$skus.'"></div>';

        return $output;
    }

    /*
     * Get Correct URL Based on Region
     */
    public function getReviewsUrl($subDomain, $store=null){
		$region = $this->config('api/reviews_region', $store);
        if($region == 'us'){
            return 'https://'.$subDomain.'.reviews.io/';
        }
        return 'https://'.$subDomain.'.reviews.co.uk/';
    }

    /*
     * Prepare and Return Widget Color as set in Config
     */
    public function getWidgetColor(){
        $colour  = $this->config('widget/product_widget_colour');
        // people will sometimes put hash and sometimes they will forgot so we need to check for this error
        if(strpos($colour,'#') === FALSE) $colour = '#'.$colour;
        // checking to see if we hare a valid colour. If not then we change it to reviews default hex colour
        if(!preg_match('/^#[a-f0-9]{6}$/i', $colour)) $colour = '#5db11f';
        return $colour;
    }

    /*
     * Generate Code for the Product Reviews widget
     */
    public function getProductWidget($product){

        $productSkus = $product? $this->getProductSkus($product) : array();

        $sku = implode(';', $productSkus);

        if($this->config('widget/product_widget_version') == '2'){
            return $this->getStaticProductWidget($sku);
        }
        else
        {
            return $this->getJavascriptProductWidget($sku);
        }
    }

    /*
     * Request the Code for the static product widget via curl
     */
    public function getStaticProductWidget($sku){
        $url = $this->getReviewsUrl('widget').'product-seo/widget?store='.$this->getStoreName().'&sku='.$sku.'&primaryClr='.urlencode($this->getWidgetColor());
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $widgetHtml = curl_exec($ch);
        curl_close($ch);
        return $widgetHtml;
    }

    /*
     * Remove Newlines and Escape Quotes in Custom Widget CSS
     */
    protected function prepareCss($css){
        $css = str_replace("\n",'', $css);
        $css = str_replace("\r",'', $css);
        $css = str_replace('"','\"', $css);
        return $css;
    }

    /*
     * Generate the code for the Javascript product widget
     */
    public function getJavascriptProductWidget($sku){
        ob_start();
        ?>
            <script src="<?php echo $this->getReviewsUrl('widget')?>product/dist.js"></script>
            <div id="widget"></div>
            <script type="text/javascript">
                productWidget("widget", {
                    store: '<?php echo $this->getStoreName(); ?>',
                    sku: '<?php echo $sku; ?>', // Multiple SKU"s Seperated by Semi-Colons
                    primaryClr: "<?php echo $this->getWidgetColor(); ?>",
                    neutralClr: "#EBEBEB",
                    buttonClr: "#EEE",
                    textClr: "#333",
                    tabClr: "#eee",
                    css: "<?php echo $this->prepareCss($this->config('advanced/product_widget_css')); ?>",
                    questions: true,
                    showTabs: true,
                    ratingStars: false,
                    showAvatars: true,
                    translateAverage: '<?php echo $this->__("Average");?>',
                    translateReviews: '<?php echo $this->__("Reviews");?>',
                    translateNoReviews: '<?php echo $this->__("No Reviews");?>',
                    translateMoreRatings: '<?php echo $this->__("More Ratings");?>',
                    translateNoComments: '<?php echo $this->__("This review has no comments");?>',
                    translateReplyFrom: '<?php echo $this->__("Reply from");?>',
                    translatePosted: '<?php echo $this->__("Posted");?>',
                    translateWriteReview: '<?php echo $this->__("Write a Review");?>',
                    translateShow: '<?php echo $this->__("Show");?>',
                    translateDetails: '<?php echo $this->__("Details");?>'
                });
            </script>
        <?php

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
