<?php

class Reviewscouk_Reviews_Block_List_Product extends Mage_Catalog_Block_Product_View
{
    const URL_REVIEW_PAGE = 'http://collector.reviews.co.uk/product-review/';

    protected $_apiModel;
    protected $_productSku;
    protected $_product;

    public function __construct()
    {
        $this->_productSku = $this->getProduct()->getSku();
        if($this->_productSku) {
            $this->addData(array(
                'cache_lifetime' => 3600,
                'cache_tags'     => array(Mage_Catalog_Model_Product::CACHE_TAG),
                'cache_key'      => $this->getProduct()->getId()
            ));
        }

        $this->_apiModel = Mage::getModel('reviewsmodel/api_product');
    }

    /**
     * @return array
     *
     * This function must be ran before any others to populate the model with data
     */
    public function getProductReviews($sku)
    {
        if($sku) {
            $this->_productSku = $sku;
        }
        return $this->_apiModel->getProductReviews($this->_productSku);
    }

    /**
     * @return string
     */
    public function getAverageRating()
    {
        return $this->_apiModel->getAverageRating();
    }

    /**
     * @param $rating int
     * @return string
     */
    public function getStars($rating)
    {
        $html = '';
        $maxRating = 5;
        $i = 1;
        while($i<=$maxRating) {

            if($i<=$rating) {
                $html .= '<img src="' . $this->getSkinUrl("images/reviewscouk/goldstar.png") . '" />';
            } else {
                $html .= '<img src="' .  $this->getSkinUrl("images/reviewscouk/greystar.png") . '" />';
            }

            $i++;
        }

        return $html;
    }

    /**
     * @return string
     */
    public function getWriteReviewUrl() {
        return self::URL_REVIEW_PAGE . $this->_apiModel->getApiStoreName() . '/' . $this->_productSku;
    }
}