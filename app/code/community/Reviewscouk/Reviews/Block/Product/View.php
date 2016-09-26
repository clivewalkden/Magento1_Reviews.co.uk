<?php
class Reviewscouk_Reviews_Block_Product_View extends Mage_Catalog_Block_Product_View {

    /*
     * Load the helper
     */
    public function __construct(){
        parent::__construct();
        $this->helper = Mage::helper('reviewshelper');
    }

    /*
     * Replace Reviews Summary with Reviews.co.uk Rating Snippet
     */
    public function getReviewsSummaryHtml(){
        $product = Mage::registry('current_product');
        return $this->helper->getRatingSnippet($product);
    }
}
