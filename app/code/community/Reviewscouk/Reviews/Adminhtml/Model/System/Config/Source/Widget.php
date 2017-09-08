<?php
class Reviewscouk_Reviews_Adminhtml_Model_System_Config_Source_Widget
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '1', 'label'=>Mage::helper('adminhtml')->__('Javascript Widget')),
            array('value' => '2', 'label'=>Mage::helper('adminhtml')->__('Static Content Widget')),
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
            '1' => Mage::helper('adminhtml')->__('V1 - Javascript Widget'),
            '2' => Mage::helper('adminhtml')->__('V2 - Static Content'),
        );
    }
}
