<?php

namespace Drupal\iq_commerce\Plugin\rest\resource;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart_api\Plugin\rest\resource\CartAddResource;
use Drupal\commerce_order\Resolver\ChainOrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\iq_commerce\Event\IqCommerceAfterCartAddEvent;
use Drupal\iq_commerce\Event\IqCommerceBeforeCartAddEvent;
use Drupal\iq_commerce\Event\IqCommerceCartEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Creates order items for the session's carts.
 *
 * @RestResource(
 *   id = "iq_commerce_cart_add",
 *   label = @Translation("Iq Commerce Cart add"),
 *   uri_paths = {
 *     "create" = "/cart/add"
 *   }
 * )
 */
class IqCommerceCartAddResource extends CartAddResource {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_order\Resolver\ChainOrderTypeResolverInterface $chain_order_type_resolver
   *   The chain order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain base price resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, CartProviderInterface $cart_provider, CartManagerInterface $cart_manager, EntityTypeManagerInterface $entity_type_manager, ChainOrderTypeResolverInterface $chain_order_type_resolver, CurrentStoreInterface $current_store, ChainPriceResolverInterface $chain_price_resolver, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $cart_provider, $cart_manager, $entity_type_manager, $chain_order_type_resolver, $current_store, $chain_price_resolver, $current_user, $entity_repository);
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
      $container->get('entity_type.manager'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Add order items to the session's carts.
   *
   * @param array $data
   *   The unserialized request body.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The resource response.
   *
   * @throws \Exception
   */
  public function post(array $data, Request $request) {
    if (empty($data)) {
      return new \InvalidArgumentException(sprintf('No data provided.'));
    }
    // Do an initial validation of the payload before any processing.
    foreach ($data as $key => $order_item_data) {
      if (!isset($order_item_data['purchased_entity_type'])) {
        throw new UnprocessableEntityHttpException(sprintf('You must specify a purchasable entity type for row: %s', $key));
      }
      if (!isset($order_item_data['purchased_entity_id'])) {
        throw new UnprocessableEntityHttpException(sprintf('You must specify a purchasable entity ID for row: %s', $key));
      }
      if (!$this->entityTypeManager->hasDefinition($order_item_data['purchased_entity_type'])) {
        throw new UnprocessableEntityHttpException(sprintf('You must specify a valid purchasable entity type for row: %s', $key));
      }
    }

    // Initialize an array with the order item fields.
    $order_item_fields = [];
    $first_item = reset($data);
    if (!empty($first_item['form_data'])) {
      foreach ($first_item['form_data'] as $field_name => $field_value) {
        $field_name = explode('[', (string) $field_name)[0];
        if (!empty($order_item_fields[$field_name])) {
          if (!is_array($order_item_fields[$field_name])) {
            $order_item_fields[$field_name] = [$order_item_fields[$field_name]];
          }
          $order_item_fields[$field_name][] = $field_value;
        }
        else {
          $order_item_fields[$field_name] = $field_value;
        }
      }
    }

    /** @var \Drupal\iq_commerce\Event\IqCommerceBeforeCartAddEvent $before_event */
    $before_event = new IqCommerceBeforeCartAddEvent($data);
    $this->eventDispatcher->dispatch($before_event, IqCommerceCartEvents::BEFORE_CART_ENTITY_ADD);
    $data = $before_event->getBody();
    // Create the order item through the commerce API.
    $response = parent::post($data, $request);
    // Go through the response, it should be only 1 order item.
    $responseData = $response->getResponseData();
    /** @var OrderItem $order_item */
    $order_item = reset($responseData);
    foreach ($order_item_fields as $field_name => $field_value) {
      if ($order_item->hasField($field_name)) {
        $order_item->set($field_name, $field_value);
      }
    }
    $order_item->save();

    $additional_data = $before_event->getAdditionalData();
    /** @var \Drupal\iq_commerce\Event\IqCommerceAfterCartAddEvent $before_event */
    $after_event = new IqCommerceAfterCartAddEvent($response, $additional_data);
    $this->eventDispatcher->dispatch($after_event, IqCommerceCartEvents::AFTER_CART_ENTITY_ADD);
    $response = $after_event->getResponseWithAdditionalData();
    return $response;
  }

}
