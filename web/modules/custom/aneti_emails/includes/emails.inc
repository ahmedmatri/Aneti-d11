<?php

/**
 * Utility functon to send an email to a specific user
 *
 * @param Drupal\user\Entity\User $user
 * @param String $subject
 * @param String $message
 * @return mixed
 */
function aneti_emails_notify_user(Drupal\user\Entity\User $user, $subject, $message) {
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
  \Drupal::service('plugin.manager.mail')
    ->mail('aneti_enhance', $key, $to, $langcode, $params);
}

/**
 * Helper function to send email to all users of a role.
 *
 * @param [type] $role
 * @param [type] $subject
 * @param [type] $message
 * @return void
 */
function aneti_emails_notify_roles(array $roles, $subject, $message) {
  // Load all users of a role.
  $users = aneti_emails_retrieve_users_of_roles($roles);
  foreach ($users as $user) {
    if ($user instanceof \Drupal\user\Entity\User) {
      $langcode = $user->getPreferredLangcode();
      $params = [
        'subject' => t('@subject', ['@subject' => $subject]),
        'message' => $message,
      ];
      // Set a unique key for this mail.
      $key = 'aneti_enhance_notify';

      $mail = $user->get('mail')->getValue();
      $to = $mail[0]['value'];

      // Send the Email.
      \Drupal::service('plugin.manager.mail')
        ->mail('aneti_enhance', $key, $to, $langcode, $params);
    }
  }
}

/**
 * @param array $roles
 *
 * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\user\Entity\User[]
 */
function aneti_emails_retrieve_users_of_roles(array $roles) {
  $uids = Drupal::entityQuery('user')
    ->condition('roles', $roles, 'IN')
    ->condition('status', 1)
    ->execute();

  return \Drupal\user\Entity\User::loadMultiple($uids);
}