# Reviews.co.uk Magento Extension

## Development Installation

This module can be installed with Modman. For instructions on how to do this, visit [the Modman Github page](https://github.com/colinmollenhour/modman)

## Package Extension

- Got to Config > Magento Connect > Package Extensions

- Load Local Package

- Save Data and Create Package

## Adding New Files

If a new file is required. Create the file in this repo, then add a symlink to setup.sh

## Basic API Templates

This plugin offers 2 basic templates that pull reviews from the API; one for product reviews and one for merchant reviews. These templates are unused by default, and so changes need to be made to code to make use of them.

These can be used in the following manner;

### Merchant Reviews

Merchant reviews be included in CMS Pages by placing the following into the WYSIWYG editor
```HTML
{{block type="reviewscoukreviews/list_merchant" name="reviewscouk.reviews.merchant" template="reviews/list/merchant.phtml"}}
```

Or included using XML

```XML
<block type="reviewscoukreviews/list_merchant" name="reviewscouk.reviews.merchant" template="reviews/list/merchant.phtml" />
```

### Product Reviews

Product reviews can be included using XML similar to what is shown below (this example replaces the standard Magento Reviews tab with reviews from Reviews.co.uk)

```XML
<catalog_product_view>
    <reference name="product.info">
        <action method="unsetChild"><name>product.reviews</name></action>
        <block type="reviewscoukreviews/list_product" name="product.reviews" as="reviews" template="reviews/list/product.phtml" after="additional">
            <action method="addToParentGroup"><group>detailed_info</group></action>
            <action method="setTitle" translate="value"><value>Reviews</value></action>
        </block>>
    </reference>
</catalog_product_view>
```

This block is designed to be used on a product page, however an SKU can be passed into the `$reviews = $this->getProductReviews('sku');` function to fetch reviews for a specific product.