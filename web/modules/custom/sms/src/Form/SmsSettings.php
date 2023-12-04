<?php

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SmsSettings Form.
 * @package Drupal\sms\Form
 */
class SmsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sms.settings');

    $form['central_config'] = array(
      '#type' => 'vertical_tabs',
    );

    $form['school_info'] = array(
      '#type' => 'details',
      '#title' => t('School Information'),
      '#collapsible' => TRUE,
      '#group' => 'central_config'
    );
    $form['school_info']['school_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School Name'),
      '#default_value' => $config->get('school_name'),
    ];
    $form['school_info']['school_contact_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact Number'),
      '#default_value' => $config->get('school_contact_number'),
    ];
    $form['school_info']['school_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#default_value' => $config->get('school_address'),
    ];

    $form['director_info'] = array(
      '#type' => 'details',
      '#title' => t('Director Information'),
      '#collapsible' => TRUE,
      '#group' => 'central_config'
    );
    $form['director_info']['director_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Director Name'),
      '#default_value' => $config->get('director_name'),
    ];
    $form['director_info']['director_contact_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact Number'),
      '#default_value' => $config->get('director_contact_number'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sms.settings')
      ->set('school_name', $form_state->getValue('school_name'))
      ->set('school_contact_number', $form_state->getValue('school_contact_number'))
      ->set('school_address', $form_state->getValue('school_address'))

      ->set('director_name', $form_state->getValue('director_name'))
      ->set('director_contact_number', $form_state->getValue('director_contact_number'))

      ->save();
  }

}