<?php

class Reviewscouk_Reviews_IndexController extends Mage_Core_Controller_Front_Action
{

	/**
	 * Index Page
	 */
	public function indexAction()
	{
		echo "Permission Denied!";
	}

	/**
	 * Product Feed
	 */
	public function feedAction()
	{
		$_configHelper = Mage::helper('reviewscouk_reviews/config');
		$productFeedEnabled = $_configHelper->isProductFeedEnabled(Mage::app()->getStore());
		if ($productFeedEnabled)
		{
			$cache = Mage::app()->getCache();
			$saveCached = $cache->load("feed");
			if(!$saveCached)
			{
				$store = Mage::app()->getStore();

				$productFeed = "<?xml version='1.0'?>
						<rss version ='2.0' xmlns:g='http://base.google.com/ns/1.0'>
						<channel>
						<title><![CDATA[" . $store->getName() . "]]></title>
						<link>" . Mage::app()->getStore()->getBaseUrl() . "</link>";

				$products = Mage::getModel('catalog/product')->getCollection();
				foreach ($products as $prod)
				{
					$product = Mage::getModel('catalog/product')->load($prod->getId());

					$brand = $product->getAttributeText('manufacturer') ? $product->getAttributeText('manufacturer') : 'Not Available';

					$price      = $product->getPrice();
					$finalPrice = $product->getFinalPrice();

					$productFeed .= "<item>
							<g:id><![CDATA[" . $product->getSku() . "]]></g:id>
							<title><![CDATA[" . $product->getName() . "]]></title>
							<link>" . $product->getProductUrl() . "</link>
							<g:price>" . number_format($price, 2) . " " . Mage::app()->getStore()->getDefaultCurrencyCode() . "</g:price>
							<g:sale_price>" . number_format($finalPrice, 2) . " " . Mage::app()->getStore()->getDefaultCurrencyCode() . "</g:sale_price>
							<description><![CDATA[" . $product->getDescription() . "]]></description>
							<g:condition>new</g:condition>
							<g:image_link>" . $product->getImageUrl() . "</g:image_link>
							<g:brand><![CDATA[" . $brand . "]]></g:brand>
							<g:mpn><![CDATA[" . $product->getSku() . "]]></g:mpn>
							<g:product_type><![CDATA[" . $product->getTypeID() . "]]></g:product_type>
							<g:shipping>
							<g:country>UK</g:country>
							<g:service>Standard Free Shipping</g:service>
							<g:price>0 GBP</g:price>
							</g:shipping>";

					$categoryCollection = $product->getCategoryCollection();
					if (count($categoryCollection) > 0)
					{
						foreach ($categoryCollection as $category)
						{
							$productFeed .= "<g:google_product_category><![CDATA[" . $category->getName() . "]]></g:google_product_category>";
						}
					}

					$stock = $product->getStockItem();
					if ($stock->getIsInStock())
					{
						$productFeed .= "<g:availability>in stock</g:availability>";
					}
					else
					{
						$productFeed .= "<g:availability>out of stock</g:availability>";
					}

					$productFeed .= "</item>";
				}

				$productFeed .= "</channel></rss>";

				$cache->save($productFeed, "feed", array("nicks_cache"), 86400);
			}
			else
			{
				$productFeed = $saveCached;
			}

			echo $productFeed;
			exit();
		}
		else
		{
			echo "Product Feed is disabled.";
		}
	}
}
