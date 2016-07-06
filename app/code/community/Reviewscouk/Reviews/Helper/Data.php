<?php

class Reviewscouk_Reviews_Helper_Data extends Mage_Core_Helper_Abstract {

    private $_configHelper;

    public function __construct()
    {
        $this->_configHelper = Mage::helper('reviewscouk_reviews/config');
    }

    public function autoRichSnippet(){
        $merchant_enabled  = $this->_configHelper->isMerchantReviewsEnabled(Mage::app()->getStore());
        $product_enabled  = $this->_configHelper->isProductReviewsEnabled(Mage::app()->getStore());

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

		$apikey = $this->_configHelper->getApiKey(Mage::app()->getStore());
		$region = $this->_configHelper->getRegion(Mage::app()->getStore());
		$storeName = $this->_configHelper->getStoreId(Mage::app()->getStore());
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

}
