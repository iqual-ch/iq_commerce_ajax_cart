<?php

namespace Drupal\iq_commerce\Event;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the cart after add event.
 *
 * @see \Drupal\iq_commerce\Event\CartEvents
 */
class IqCommerceAfterCartAddEvent extends Event {

  /**
   * The response after the product item(s) is added to the cart.
   *
   * @var \Drupal\rest\ResourceResponseInterface
   */
  protected $response;

  /**
   * The additional data to the response.
   *
   * @var array
   */
  protected $additionalData;

  /**
   * Constructs a new AfterCartAddEvent.
   *
   * @param \Drupal\rest\ResourceResponseInterface $response
   *   The response after the order item(s) is added to the cart.
   * @param array $additionalData
   *   The additional data to be added.
   */
  public function __construct(ResourceResponseInterface $response, array $additionalData = []) {
    $this->response = $response;
    $this->additionalData = $additionalData;
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

  /**
   * Gets the response.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response with the additional data.
   */
  public function getResponseWithAdditionalData() {
    $order_items = $this->response->getResponseData();
    $data = array_merge(['order_items' => array_values($order_items)], $this->additionalData);
    $response = new ModifiedResourceResponse($data, 200);
    return $response;
  }

  /**
   * Adds additional data to the response.
   *
   * @param array $data
   *   The additional data to be added.
   */
  public function addAdditionalData($data) {
    $this->additionalData = array_merge($this->additionalData, $data);
  }

}
