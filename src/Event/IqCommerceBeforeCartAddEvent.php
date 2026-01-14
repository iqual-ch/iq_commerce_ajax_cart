<?php

namespace Drupal\iq_commerce\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the before cart add event.
 *
 * @see \Drupal\iq_commerce\Event\CartEvents
 */
class IqCommerceBeforeCartAddEvent extends Event {

  /**
   * Constructs a new BeforeCartAddEvent.
   *
   * @param array $body
   *   The json data of the request to add order item(s) sent to the cart api.
   * @param array $additionalData
   *   The additional data to be added.
   */
  public function __construct(protected $body, protected $additionalData = []) {
  }

  /**
   * Sets the body.
   *
   * @param array $body
   *   The json data of the request to add order item(s) sent to the cart api.
   */
  public function setBody($body) {
    return $this->body = $body;
  }

  /**
   * Sets the additional data.
   *
   * @param array $data
   *   The additional data to be added.
   */
  public function setAdditionalData($data) {
    $this->additionalData = $data;
  }

  /**
   * Gets the additional data.
   *
   * @return array
   *   The additional data.
   */
  public function getAdditionalData(): array {
    return $this->additionalData;
  }

  /**
   * Gets the body.
   *
   * @return array
   *   The body.
   */
  public function getBody(): array {
    return $this->body;
  }

}
