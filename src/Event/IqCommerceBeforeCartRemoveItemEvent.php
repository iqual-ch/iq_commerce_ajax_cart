<?php

namespace Drupal\iq_commerce\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the before cart remove item event.
 *
 * @see \Drupal\iq_commerce\Event\CartEvents
 */
class IqCommerceBeforeCartRemoveItemEvent extends Event {

  /**
   * The order that is being edited.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $commerceOrder;

  /**
   * The ordered item that is being removed from the cart.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $commerceOrderItem;

  /**
   * Constructs a new BeforeCartRemoveItemEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order that is being edited.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $commerce_order_item
   *   The order item that is being removed.
   */
  public function __construct($commerce_order, $commerce_order_item) {
    $this->commerceOrder = $commerce_order;
    $this->commerceOrderItem = $commerce_order_item;

  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->commerceOrder;
  }

  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getOrderItem() {
    return $this->commerceOrderItem;
  }

}
