<?php

namespace Drupal\single_node_form_alerts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Class NodeFormMessage.
 */
class NodeFormMessage extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityFieldManager definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;
  /**
   * Constructs a new NodeFormMessage object.
   */

  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityFieldManager $entity_field_manager
    ) {
    parent::__construct($config_factory);
        $this->entityFieldManager = $entity_field_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'single_node_form_alerts.nodeformalert',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_form_alert';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = null) {

    $nid = $node->id();
    $state_id = 'single_node_form_alerts.node_'.$nid;
    $config = \Drupal::state()->get($state_id);

    $form['title']['#markup'] = '<h2>'.$this->t('Edit node form messages for @title', ['@title' => $node->getTitle()]).'</h2>';
    $form['title']['#description'] = 'To remove a message, set it to empty in this form and save';

    $form['node_id'] = array(
      '#type' => 'hidden',
      '#value' => $nid,
    );

    $fd = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->getType());
    $node_fields = array_filter($fd, function($a){
      if(method_exists($a, 'getEntityTypeId') && $a->getEntityTypeId() == 'field_config'){
        return true;
      }
    });

    $textfield = [
      '#type' => 'textarea',
      '#title' => t('Message Content')
    ];

    $form['top'] = [
      '#type' => 'details',
      '#title' => t('Message for top of form'),
      '#open' => FALSE,
    ];

    $form['top']['top_message'] = $textfield;
    $form['top']['top_message']['#default_value'] = !empty($config['top_message']) ? $config['top_message'] : '';

    foreach($node_fields as $fieldname => $field) {
      $form[$fieldname] = [
        '#type' => 'details',
        '#title' => t('Message for @label', ['@label' => $field->label()]),
        '#open' => FALSE,
      ];
      $form[$fieldname][$fieldname.'_message'] = $textfield;
      $form[$fieldname][$fieldname.'_message']['#default_value'] = !empty($config[$fieldname.'_message']) ? $config[$fieldname.'_message'] : '';
    }

    $form['bottom'] = [
      '#type' => 'details',
      '#title' => t('Message for bottom of form'),
      '#open' => FALSE,
    ];
    $form['bottom']['bottom_message'] = $textfield;
    $form['bottom']['bottom_message']['#default_value'] = !empty($config['bottom_message']) ? $config['bottom_message'] : '';

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
    $values = $form_state->getValues();
    $state_id = 'single_node_form_alerts.node_'.$values['node_id'];
    $message_config = array_filter($values, function ($v, $k) {
      if(!is_object($v) && !empty($v) && strpos($k, 'message') !== false) {
        return true;
      }
    }, ARRAY_FILTER_USE_BOTH );
    \Drupal::state()->set($state_id, $message_config);
  }

  public function title(NodeInterface $node) {
    return t('Form Messages for @title', ['@title' => $node->getTitle()]);
  }

}
