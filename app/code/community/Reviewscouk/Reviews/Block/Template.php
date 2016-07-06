<?php

class Reviewscouk_Reviews_Block_Template extends Mage_Core_Block_Template
{

    private $_configHelper;

    public function __construct()
    {
        $this->_configHelper = Mage::helper('reviewscouk_reviews/config');
    }

    public function isProductWidgetEnabled() {
        return $this->_configHelper->isProductWidgetEnabled(Mage::app()->getStore());
    }

}