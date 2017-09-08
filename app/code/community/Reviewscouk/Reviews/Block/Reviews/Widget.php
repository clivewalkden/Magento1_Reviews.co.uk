<?php
class Reviewscouk_Reviews_Block_Reviews_Widget extends Reviewscouk_Reviews_Block_Reviews_Abstract {

    /*
     * Include the Product Widget for Current Product (If Enabled)
     */
    public function includeProductWidget(){
        $enabled  = $this->helper->config('widget/product_widget_enabled');
        if ($enabled){
            $product = Mage::registry('current_product');
            return $this->helper->getProductWidget($product);
        }
    }
}
