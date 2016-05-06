<?php

class Reviewscouk_Reviews_Block_List_Merchant extends Mage_Core_Block_Template
{
    const URL_REVIEW_PAGE = 'http://www.reviews.co.uk/company-reviews/store/';

    protected $_apiModel;

    public function __construct()
    {
        $this->setCacheLifetime(3600);

        $this->_apiModel = Mage::getModel('reviewsmodel/api_merchant');
    }

    /**
     * @return mixed
     *
     * This function must be ran before any others to populate the model with data
     */
    public function getMerchantReviews()
    {
        return $this->_apiModel->getMerchantReviews();
    }

    public function getAverageRating()
    {
        return $this->_apiModel->getAverageRating();
    }

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

    public function getReviewPageUrl() {
        return self::URL_REVIEW_PAGE . $this->_apiModel->getApiStoreName();
    }
}