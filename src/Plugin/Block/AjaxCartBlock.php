<?php

namespace Drupal\iq_commerce_ajax_cart\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\iq_progressive_decoupler\Plugin\Block\DecoupledBlockBase;

/**
 * AJAX Cart Block.
 *
 * @Block(
 *   id = "iq_commerce_ajax_cart_block",
 *   admin_label = @Translation("AJAX Cart Block"),
 * )
 */
class AjaxCartBlock extends DecoupledBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Cart'),
      '#default_value' => $this->configuration['link'] ?? '/cart',
    ];

    $form['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Title'),
      '#default_value' => $this->configuration['link_title'] ?? 'Cart',
    ];

    $form['label_total'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label Total'),
      '#default_value' => $this->configuration['label_total'] ?? 'Total',
    ];

    $form['label_cart_empty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label Cart empty'),
      '#default_value' => $this->configuration['label_cart_empty'] ?? 'Empty',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $build['#theme'] = 'iq_commerce_ajax_cart_block';
    $build['#attached']['library'][] = 'iq_commerce_ajax_cart/ajax-cart';

    $build['#cache'] = [
      'max-age' => 0,
    ];

    $build['#label'] = $this->label();
    if (isset($this->configuration['link'])) {
      $build['#link'] = $this->configuration['link'];
    }

    if (isset($this->configuration['link_title'])) {
      $build['#link_title'] = $this->configuration['link_title'];
    }

    if (isset($this->configuration['label_cart_empty'])) {
      $build['#label_cart_empty'] = $this->configuration['label_cart_empty'];
    }

    if (isset($this->configuration['label_total'])) {
      $build['#label_total'] = $this->configuration['label_total'];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['link'] = $form_state->getValue('link');
    $this->configuration['link_title'] = $form_state->getValue('link_title');
    $this->configuration['label_total'] = $form_state->getValue('label_total');
    $this->configuration['label_cart_empty'] = $form_state->getValue('label_cart_empty');
  }

}
