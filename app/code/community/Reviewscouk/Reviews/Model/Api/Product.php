<?php

class Reviewscouk_Reviews_Model_Api_Product extends Reviewscouk_Reviews_Model_Api
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

    public function getProductReviews($sku)
    {

        $data = $this->curlProductReviews($sku);

        $this->_totalPages = $data->reviews->last_page;
        $this->_totalReviews = $data->stats->count;
        $this->_averageRating = $data->stats->average;

        return $data->reviews->data;
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
    protected function curlProductReviews($sku)
    {

        $url = self::API_URL_PRODUCT_REVIEWS;
        $params = array(
            'store' => $this->getApiStoreName(),
            'sku' => $sku,
            'per_page' => 25,
            'include_replies' => 1,
            'order' => 'desc'
        );

        $data = $this->processCurl($url, $params);
        return json_decode($data);
    }


}