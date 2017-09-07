<?php
class Reviewscouk_Reviews_Adminhtml_Model_System_Config_Source_Region
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'UK', 'label'=>Mage::helper('adminhtml')->__('UK')),
            array('value' => 'US', 'label'=>Mage::helper('adminhtml')->__('US')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'UK' => Mage::helper('adminhtml')->__('UK'),
            'US' => Mage::helper('adminhtml')->__('US'),
        );
    }
}
