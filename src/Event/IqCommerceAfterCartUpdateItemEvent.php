<?php

namespace Drupal\iq_commerce\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the after cart update item event.
 *
 * @see \Drupal\iq_commerce\Event\CartEvents
 */
class IqCommerceAfterCartUpdateItemEvent extends Event {

  /**
   * Constructs a new AfterCartUpdateEvent.
   *
   * @param \Drupal\rest\ResourceResponseInterface $response
   *   The response after the order item is updated from the cart.
   */
  public function __construct(protected $response) {
  }

  /**
   * Gets the response.
   *
   * @return \Drupal\rest\ResourceResponseInterface
   *   The response from the cart api.
   */
  public function getResponse() {
    return $this->response;
  }

}
