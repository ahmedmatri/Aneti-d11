<?php

namespace Drupal\email_updater\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class ChangeEmailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_updater_change_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Change type to 'entity_autocomplete'
    $form['user_select'] = [
'#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Select User'),
      '#description' => $this->t('Start typing the username or email of the user.'),
      '#required' => TRUE,
      // Optional: Prevent selecting the Anonymous user (UID 0)
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
    ];

    $form['new_email'] = [
      '#type' => 'email',
      '#title' => $this->t('New Email Address'),
      '#required' => TRUE,
    ];
$form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Email'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
public function submitForm(array &$form, FormStateInterface $form_state) {
    // The autocomplete field returns the UID directly
    $uid = $form_state->getValue('user_select');
    $new_email = $form_state->getValue('new_email');

    $user = User::load($uid);

    if ($user) {
      $old_email = $user->getEmail();
      $user->setEmail($new_email);
      $user->save();

      $this->messenger()->addStatus($this->t('Success! User %name (UID %uid) updated from %old to %new.', [
        '%name' => $user->getAccountName(),
        '%uid'  => $uid,
        '%old'  => $old_email,
        '%new'  => $new_email,
      ]));
    }
  }
}
