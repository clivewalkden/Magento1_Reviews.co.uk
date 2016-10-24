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
    public function getReviewsSummaryHtml(Mage_Catalog_Model_Product $product, $templateType = false, $displayIfNoReviews = false){
        return $this->helper->getRatingSnippet($product);
    }
}
