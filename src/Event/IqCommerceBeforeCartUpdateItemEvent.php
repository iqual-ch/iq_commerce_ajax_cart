<?php

namespace Drupal\iq_commerce\Event;

/**
 * Defines the before cart update item event.
 *
 * @see \Drupal\iq_commerce\Event\CartEvents
 */
class IqCommerceBeforeCartUpdateItemEvent extends IqCommerceBeforeCartRemoveItemEvent {

  /**
   * Constructs a new BeforeCartUpdateItemEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order that is being edited.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $commerce_order_item
   *   The order item that is being removed.
   * @param mixed $unserialized
   *   The unserialized data from the request body.
   */
  public function __construct($commerce_order, $commerce_order_item, protected $unserialized) {
    parent::__construct($commerce_order, $commerce_order_item);

  }

  /**
   * Gets the unseralized data.
   *
   * @return mixed
   *   The unserialized data from the request body.
   */
  public function getUnserialized() {
    return $this->unserialized;
  }

}
