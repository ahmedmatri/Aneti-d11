<?php

namespace Drupal\aneti_enhance\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Mail\MailManager;
use Symfony\Component\DependencyInjection\ContainerInterface ;

class ViewsBulkOperationPostulationActionBase extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * @param \Drupal\node\Entity\Node $entity
   *
   * @return bool
   */
  public function execute($entity = NULL) {

    if ($entity->getType() != 'postulation') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

}
