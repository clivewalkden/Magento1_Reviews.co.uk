<?php

/**
 * Class Reviewscouk_Reviews_Model_Api_Merchant
 */
class Reviewscouk_Reviews_Model_Api_Merchant extends Reviewscouk_Reviews_Model_Api
{

    protected $_totalReviews;
    protected $_totalPages;
    protected $_averageRating;

    public function __construct()
    {
        $this->_totalPages = 0;
        $this->_totalReviews = 0;
        $this->_averageRating = 0;
    }

    public function getMerchantReviews()
    {

        $data = $this->curlMerchantReviews();

        $this->_totalPages = $data->total_pages;
        $this->_totalReviews = $data->stats->total_reviews;
        $this->_averageRating = $data->stats->average_rating;

        return $data->reviews;
    }

    public function getAverageRating()
    {
        return $this->_averageRating;
    }

    public function getTotalPages()
    {
        return $this->_totalPages;
    }

    public function getTotalReviews()
    {
        return $this->_totalReviews;
    }

    /*
     * Factory API methods
     */
    protected function curlMerchantReviews()
    {

        $url = self::API_URL_MERCHANT_REVIEWS;
        $params = array(
            'store' => $this->getApiStoreName(),
            'per_page' => 25,
            'include_replies' => 1,
            'order' => 'desc'
        );

        $data = $this->processCurl($url, $params);
        return json_decode($data);
    }

}