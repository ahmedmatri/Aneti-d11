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

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "views_bulk_operations_revive_company",
 *   label = @Translation("Revive compnay"),
 *   type = "profile",
 *   confirm = TRUE,
 * )
 */
class ViewsBulkOperationReviveCompanyAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Mail manager object so we can send emails.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * RoleEmailAction constructor.
   *
   * @param array $configuration
   *   Configuration Array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Mixed Plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManager $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mailManager = $mail_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $uid = $entity->get('uid')->getValue()[0]['target_id'];
    $this->sendMails($uid, $this->t('You have been inactive since X'), $this->t('This is a revivle email'));
    
    if ($referant = $this->getReferant($entity)) {
      $this->sendMails($referant, $this->t('An Entreprise has been inactive since X'), $this->t('This is a revivle email'));
    }
    //dsm($referant);
    return sprintf('Emails sent to selected companies.');
  }

  /**
   * Get referant user id from profile.
   *
   * @param [type] $entity
   * @return void
   */
  protected function getReferant($entity) {
    $uid = $entity->get('field_referant')->getValue();
    if (isset($uid[0]['target_id'])) {
      return $uid[0]['target_id'];
    }
    return FALSE;
  }

  protected function sendMails($uid, $subject, $message) {
    // @todo custimize this email.
    if ($user = \Drupal\user\Entity\User::load($uid)) {
      // Set a unique key for this mail.
      $key = 'aneti_enhance_notify';
      $langcode = $user->getPreferredLangcode();
      $params = [
        'subject' => t('@subject', ['@subject' => $subject]),
        'message' => $message,
      ];
      $mail = $user->get('mail')->getValue();
      $to = $mail[0]['value'];

      // Send the Email.
      $this->mailManager->mail('aneti_enhance', $key, $to, $langcode, $params);
    }
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
