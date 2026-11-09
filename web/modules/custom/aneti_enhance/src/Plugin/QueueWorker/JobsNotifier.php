<?php

namespace Drupal\aneti_enhance\Plugin\QueueWorker;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;


/**
 * A Jobs notifier that sends emails to users on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_jobs_notifier",
 *   title = @Translation("Cron Jobs notifier"),
 *   cron = {"time" = 100}
 * )
 */
class JobsNotifier extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  protected $mailManager;

  public function __construct(MailManagerInterface $mailManager) {
    $this->mailManager = $mailManager;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    return $this->notifyUser($data->email, $data->url);
  }

  public function notifyUser($email, $url) {
    $mailManager = $this->mailManager;
    $module = 'aneti_enhance';
    $key = 'aneti_enhance_jobletter';
    $params['message'] = $this->getMessage($url);
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $result = $mailManager->mail($module, $key, $email, $langcode, $params, NULL, $send);
    return $result;
  }

  public function getMessage($url) {
    $dns = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $url = "http://aneti-international.tn$url";
    $message = '<p>Hi,</p>';
    $message .= '<p>A new offer matching your profile has been published on the website.</p>';
    $message .= "<p>Visit the offer: $url</p>";
    $message .= '<p></p>';
    $message .= '<p>ANETI Team,</p>';
    return $message;
  }
}