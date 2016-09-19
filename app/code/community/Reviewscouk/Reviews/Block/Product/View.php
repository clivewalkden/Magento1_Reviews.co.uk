<?php
class Reviewscouk_Reviews_Block_Product_View extends Mage_Catalog_Block_Product_View {

    public function __construct(){
        parent::__construct();
        $this->helper = Mage::helper('reviewshelper');
    }

    /*
     * Add Reviews Tab
     */
    public function getTabs(){
        $tabs = parent::getTabs();

        /*$tabs[] = array(
            'alias' => 'reviews',
            'title' => 'Reviews'
        );*/

        return $tabs;
    }

    /*
     * Replace Reviews Summary with Reviews.co.uk Rating Snippet
     */
    public function getReviewsSummaryHtml(){
        $product = Mage::registry('current_product');
        return $this->helper->getRatingSnippet($product);
    }

    /*
     * Render Reviews Tab
     */
    public function getChildHtml($alias){

        /*if($alias == 'reviews'){
            return 'testing';
        }*/

        return parent::getChildHtml($alias);
    }
}
