<?php

/**
 * Class Reviewscouk_Reviews_Model_Api
 * TODO:- Break this apart into an Abstract function
 */
class Reviewscouk_Reviews_Model_Api extends Mage_Core_Model_Abstract
{

    const XML_CONFIG_REVIEWSCOUK_API_KEY = 'reviewscouk_reviews_settings/api/reviews_api_key';
    const XML_CONFIG_REVIEWSCOUK_REGION = 'reviewscouk_reviews_settings/api/reviews_region';
    const XML_CONFIG_REVIEWSCOUK_STORE_NAME = 'reviewscouk_reviews_settings/api/reviews_store_id';
    const API_URL_MERCHANT_REVIEWS = 'https://api.reviews.co.uk/merchant/reviews';
    const API_URL_PRODUCT_REVIEWS = 'https://api.reviews.co.uk/product/review';

    /*
     * Factory API methods
     */
    protected function processCurl($url, $params)
    {
        $paramStrings = array();
        foreach ($params as $param => $value) {
            $paramStrings[] = $param . '=' . $value;
        }
        $queryString = implode('&', $paramStrings);

        $ch = curl_init($url . "?" . $queryString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        return curl_exec($ch);
    }


    /*
     * Store Config Fetch functions
     */
    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $apikey = Mage::getStoreConfig(self::XML_CONFIG_REVIEWSCOUK_API_KEY, Mage::app()->getStore());
    }

    /**
     * @return mixed
     */
    public function getApiRegion()
    {
        return $apikey = Mage::getStoreConfig(self::XML_CONFIG_REVIEWSCOUK_REGION, Mage::app()->getStore());
    }

    /**
     * @return mixed
     */
    public function getApiStoreName()
    {
        return $apikey = Mage::getStoreConfig(self::XML_CONFIG_REVIEWSCOUK_STORE_NAME, Mage::app()->getStore());
    }

}