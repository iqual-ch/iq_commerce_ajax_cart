<?php

namespace Drupal\iq_commerce\Plugin\rest\resource;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart_api\Plugin\rest\resource\CartRemoveItemResource;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\iq_commerce\Event\IqCommerceAfterCartRemoveItemEvent;
use Drupal\iq_commerce\Event\IqCommerceBeforeCartRemoveItemEvent;
use Drupal\iq_commerce\Event\IqCommerceCartEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a cart collection resource for current session.
 *
 * @RestResource(
 *   id = "iq_commerce_cart_remove_item",
 *   label = @Translation("Iq Commerce Cart remove item"),
 *   uri_paths = {
 *     "canonical" = "/cart/{commerce_order}/items/{commerce_order_item}"
 *   }
 * )
 */
class IqCommerceCartRemoveResource extends CartRemoveItemResource {

  /**
   * The entity repository.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CartAddResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, CartProviderInterface $cart_provider, CartManagerInterface $cart_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $cart_provider, $cart_manager);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * DELETE an order item from a cart.
   *
   * The ResourceResponseSubscriber provided by rest.module gets weird when
   * going through the serialization process. The method is not cacheable and
   * it does not have a body format, causing it to be considered invalid.
   *
   * @todo Investigate if we can return updated order as response.
   *
   * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::getResponseFormat
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $commerce_order_item
   *   The order item.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   */
  public function delete(OrderInterface $commerce_order, OrderItemInterface $commerce_order_item) {
    /** @var \Drupal\iq_commerce\Event\IqCommerceBeforeCartRemoveItemEvent $before_event */
    $before_event = new IqCommerceBeforeCartRemoveItemEvent($commerce_order, $commerce_order_item);
    $this->eventDispatcher->dispatch($before_event, IqCommerceCartEvents::BEFORE_CART_ENTITY_REMOVE_ITEM);
    $response = parent::delete($commerce_order, $commerce_order_item);
    /** @var \Drupal\iq_commerce\Event\IqCommerceAfterCartRemoveItemEvent $before_event */
    $after_event = new IqCommerceAfterCartRemoveItemEvent($response);
    $this->eventDispatcher->dispatch($after_event, IqCommerceCartEvents::AFTER_CART_ENTITY_REMOVE_ITEM);
    $response = $after_event->getResponse();
    return $response;
  }

}
