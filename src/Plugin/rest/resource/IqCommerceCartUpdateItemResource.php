<?php

namespace Drupal\iq_commerce\Plugin\rest\resource;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart_api\Plugin\rest\resource\CartUpdateItemResource;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\iq_commerce\Event\IqCommerceAfterCartUpdateItemEvent;
use Drupal\iq_commerce\Event\IqCommerceBeforeCartUpdateItemEvent;
use Drupal\iq_commerce\Event\IqCommerceCartEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Provides a resource for updating the quantity of a cart's single order item.
 *
 * @RestResource(
 *   id = "iq_commerce_cart_update_item",
 *   label = @Translation("Iq Commerce Cart item update"),
 *   uri_paths = {
 *     "canonical" = "/cart/{commerce_order}/items/{commerce_order_item}"
 *   }
 * )
 */
class IqCommerceCartUpdateItemResource extends CartUpdateItemResource {

  /**
   * The event dispatcher.
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
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, CartProviderInterface $cart_provider, CartManagerInterface $cart_manager, SerializerInterface $serializer, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $cart_provider, $cart_manager, $serializer, $entity_type_manager);
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
      $container->get('serializer'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * PATCH to update order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $commerce_order_item
   *   The order item.
   * @param array $unserialized
   *   The request body.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function patch(OrderInterface $commerce_order, OrderItemInterface $commerce_order_item, array $unserialized) {
    if (count($unserialized) > 1 || empty($unserialized['quantity'])) {
      throw new UnprocessableEntityHttpException('You only have access to update the quantity');
    }
    if ($unserialized['quantity'] < 1) {
      throw new UnprocessableEntityHttpException('Quantity must be positive value');
    }

    /** @var \Drupal\iq_commerce\Event\IqCommerceBeforeCartUpdateItemEvent $before_event */
    $before_event = new IqCommerceBeforeCartUpdateItemEvent($commerce_order, $commerce_order_item, $unserialized);
    $this->eventDispatcher->dispatch($before_event, IqCommerceCartEvents::BEFORE_CART_ENTITY_UPDATE_ITEM);
    $response = parent::patch($commerce_order, $commerce_order_item, $unserialized);
    /** @var \Drupal\iq_commerce\Event\IqCommerceAfterCartUpdateItemEvent $before_event */
    $after_event = new IqCommerceAfterCartUpdateItemEvent($response);
    $this->eventDispatcher->dispatch($after_event, IqCommerceCartEvents::AFTER_CART_ENTITY_UPDATE_ITEM);
    $response = $after_event->getResponse();
    return $response;
  }

}
