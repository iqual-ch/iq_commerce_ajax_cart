<?php

namespace Drupal\iq_commerce_ajax_cart;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\ProductLazyBuilders as ProductLazyBuildersBase;

/**
 * Provides #lazy_builder callbacks.
 */
class ProductLazyBuilders extends ProductLazyBuildersBase {

  /**
   * {@inheritdoc}
   */
  public function addToCartForm($productId, $view_mode, $combine, $langcode) {
    $build = parent::addToCartForm($productId, $view_mode, $combine, $langcode);
    $build['#attached']['drupalSettings']['add_to_ajax_cart']['mapping_varation_attributes'] = $this->getProductVariations($productId);
    return $build;
  }

  /**
   * Loads all variations with ther attribute values.
   *
   * @param string $productId
   *   The ID of the product.
   */
  public function getProductVariations($productId) {
    $product = Product::load($productId);
    $variations = [];

    foreach ($product->getVariations() as $variation) {
      $variations[$variation->id()] = $variation->getAttributeValueIds();
    }

    return $variations;
  }

}
