<?php

namespace Drupal\aneti_candidat\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DefaultSubscriber.
 */
class DefaultSubscriber implements EventSubscriberInterface {


  /**
   * Constructs a new DefaultSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.request'] = ['kernel_request'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function kernel_request(Event $event) {
    //kint($event->getRequest()->getPathInfo());
    $account = \Drupal::currentUser();
    $user_role = $account->getRoles();

    if (\Drupal::service('path.current')->getPath() == '/node/add/curriculum_vitae' && $this->userIsCandidat() && $this->candidatHasCV($account->id())) {
      drupal_set_message('You already have a CV.', 'status', TRUE);
      // Redirect to home page.
      $response = new RedirectResponse('/mon-cv');
      $event->setResponse($response);
    }
  }

  protected function userIsCandidat() {
    $user_role = \Drupal::currentUser()->getRoles();
    if (in_array('candidat', $user_role)) {
      return TRUE;
    }
    return FALSE;
  }

  protected function candidatHasCV($uid) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'curriculum_vitae')
      ->condition('uid', $uid);
    $result = $query->execute();

    if (!empty($result)) {
      return TRUE;
    }
    return FALSE;
  }

}
