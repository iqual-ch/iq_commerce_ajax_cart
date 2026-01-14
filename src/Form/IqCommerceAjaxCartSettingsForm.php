<?php

namespace Drupal\iq_commerce_ajax_cart\Form;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Yaml as YamlSerializer;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Configure IQ Commerce Ajax Cart settings for this site.
 *
 * @package Drupal\iq_commerce_ajax_cart\Form
 */
class IqCommerceAjaxCartSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iq_commerce_ajax_cart_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $iqCommerceAjaxCartSettingsConfig = $this->config('iq_commerce_ajax_cart.settings');
    $form['general'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IQ Commerce Ajax Cart settings'),
      '#description' => $this->t('This text field should be written in yml syntax.'),
      '#default_value' => Yaml::decode($iqCommerceAjaxCartSettingsConfig->get('general') ?? ''),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      Yaml::encode($form_state->getValue('general'));
    }
    catch (InvalidDataTypeException) {
      $form_state->setErrorByName(
        'general',
        $this->t('The provided configuration is not a valid yaml text.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('iq_commerce_ajax_cart.settings')
      ->set('general', Yaml::encode($form_state->getValue('general')))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get Editable config names.
   *
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['iq_commerce_ajax_cart.settings'];
  }

  /**
   * Helper function to return the yml product settings parsed in array.
   */
  public static function getIqCommerceAjaxCartSettings() {
    $iqCommerceAjaxCartSettingsConfig = \Drupal::config('iq_commerce_ajax_cart.settings');
    if (!empty($iqCommerceAjaxCartSettingsConfig->get('general'))) {
      return YamlParser::parse(YamlSerializer::decode($iqCommerceAjaxCartSettingsConfig->get('general')));
    }
  }

}
