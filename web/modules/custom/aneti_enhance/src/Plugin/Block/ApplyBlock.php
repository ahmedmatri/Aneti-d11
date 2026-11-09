<?php

namespace Drupal\aneti_enhance\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'ApplyBlock' block.
 *
 * @Block(
 *  id = "apply_block",
 *  admin_label = @Translation("Apply block"),
 *  context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class ApplyBlock extends BlockBase {

  /**
   * Helper function that maps required field for a given language.
   * @param $language
   *
   * @return bool|mixed
   */
  protected function languageFieldMap($language) {

    if (!in_array($language, ['AR', 'FR', 'EN', 'CA'])) {
      return FALSE;
    }

    $map = [
      'AR' => 'field_cv_arabe',
      'FR' => 'field_cv_francais',
      'EN' => 'field_cv_anglais',
      'CA' => 'field_cv_canadien',
    ];

    return $map[$language];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $node_array = $this->getContext('node')->getContextData()->toArray();
    $nid = $node_array['nid'][0]['value'];

    // Get the required CV language if it exists.
    $langue_exigee = array_shift($node_array['field_langue_de_cv_exigee']);
    $langue_exigee = $langue_exigee['value'];

    // Get required cv language if set.
    $field = $this->languageFieldMap($langue_exigee);
    $allow_application = TRUE;

    // Load user's CV and check the CV exists
    if ($field && $cv = $this->getUserCV()) {
      $lang = $cv->get($field)->getValue();
      if (empty($lang)) {
        $allow_application = FALSE;
        drupal_set_message($this->t('In order to apply, you must upload a cv in the following language: @lang', ['@lang' => $langue_exigee]), 'error');
      }
    }

    // If user has CV and application is allowed.
    if ($this->UserHasCv() && $allow_application) {
      $title = $this->t('Apply this Job');
      $link = "/node/" . $nid . "/apply";
    }

    // If user has cv and missing required CV for given language.
    if ($this->UserHasCv() && !$allow_application) {
      $title = $this->t('Edit your CV');
      $link = "/mon-cv";
    }

    // If no CV found for the user
    if (!$this->UserHasCv()) {
      $title = $this->t('Add a CV');
      $link = "/node/add/curriculum_vitae";
    }

    // Look for application in progress.
    if ($this->UserHasApplication($nid)) {
      return [
        '#markup' => '<a href="#" class="apply-job">' . $this->t('Applied') . '</a>'
      ];
    }

    // No previous applications.
    $build = [];
    $build['apply_link'] = [
      '#type' => 'link',
      '#title' => $title,
      '#attributes' => [
        'class' => 'apply-job',
      ],
      '#url' => Url::fromUserInput($link),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  protected function getUserCV() {
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('status', 1)
      ->condition('type', 'curriculum_vitae')
      ->condition('field_candidat', \Drupal::currentUser()->id())
      ->execute();

    if ($query_result) {
      return node_load(array_shift($query_result));
    }
    return FALSE;
  }

  protected function UserHasCv() {
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('status', 1)
      ->condition('type', 'curriculum_vitae')
      ->condition('uid', \Drupal::currentUser()->id())
      ->execute();

    if ($query_result) {
      return TRUE;
    }
    return FALSE;
  }

  protected function UserHasApplication($nid) {
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('field_candidats', \Drupal::currentUser()->id())
      ->condition('field_intitul_de_l_offre', $nid)
      ->condition('type', 'postulation')
      ->execute();

    if ($query_result) {
      return TRUE;
    }
    return FALSE;
  }
}
