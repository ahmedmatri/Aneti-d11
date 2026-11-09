<?php

namespace Drupal\aneti_enhance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class MyProfileController.
 */
class MyProfileController extends ControllerBase {

  /**
   * Redirect.
   *
   * @return string
   *   Return Hello string.
   */
  public function redirectToProfile() {
    $uid = \Drupal::currentUser()->id();
    $roles = \Drupal::currentUser()->getRoles();

    if (in_array('candidat', $roles)) {
      return new RedirectResponse("/user/$uid/candidat");
    }

    if (in_array('entreprise', $roles)) {
      //return new RedirectResponse("/user/$uid/entreprise");
    }

    if (in_array('referent', $roles)) {
      return new RedirectResponse("/user/$uid/referent");
    }

    return $this->redirect('<front>');
  }

}
