<?php
abstract class Reviewscouk_Reviews_Block_Reviews_Abstract extends Mage_Core_Block_Template {

    /*
     * Make helper available on $this->helper
     */
    public function __construct(){
        parent::__construct();
        $this->helper = Mage::helper('reviewshelper');
    }
}
