<?php

class Reviewscouk_Reviews_Block_Productreviewwidget extends Reviewscouk_Reviews_Block_Template
{

    private $_configHelper;

    public function __construct() {
        $this->_configHelper = Mage::helper('reviewscouk_reviews/config');
        parent::__construct();
    }

    public function isInlineWidget() {
        $productWidgetVersion = $this->_configHelper->getProductWidgetVersion(Mage::app()->getStore());

        if($productWidgetVersion == '2') {
            return false;
        } else {
            return true;
        }
    }

    public function getIframeWidget() {
        $url = 'https://widget.reviews.co.uk/product-seo/widget?store='.$store_id.'&sku='.implode(';',$productSkus).'&primaryClr='.urlencode($colour);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $widgetHtml = curl_exec($ch);
        curl_close($ch);
        return $widgetHtml;
    }

    public function getData() {
        $data = array(
            'store_id' => $this->_configHelper->getStoreId(Mage::app()->getStore()),
            'api_url' => $this->getWidgetURL(),
            'colour' => $this->getWidgetColor(),
        );

        return $data;
    }

    protected function getProductSkus() {
        $skus = array();

        if(Mage::registry('current_product'))
        {
            $skus = Mage::helper('reviewscoouk_reviews')->getProductSkus(Mage::registry('current_product'));
        }

        return $skus;
    }

    protected function getWidgetColor(){
        $colour  = $this->_configHelper->getProductWidgetColour(Mage::app()->getStore());
        // people will sometimes put hash and sometimes they will forgot so we need to check for this error
        if(strpos($colour,'#') === FALSE) $colour = '#'.$colour;
        // checking to see if we hare a valid colour. If not then we change it to reviews default hex colour
        if(!preg_match('/^#[a-f0-9]{6}$/i', $colour)) $colour = '#5db11f';
        return $colour;
    }

    protected function getWidgetURL(){
        $region   = $this->_configHelper->getRegion(Mage::app()->getStore());
        $api_url = 'widget.reviews.co.uk';
        if ($region == 'US') $api_url = 'widget.review.io';
        return $api_url;
    }

}