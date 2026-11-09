<?php

namespace Drupal\aneti_enhance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use \Drupal\node\Entity\Node;

/**
 * Class AnetiApplyController.
 */
class AnetiApplyController extends ControllerBase {

  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function index($nid) {
    if ($cv = $this->getUserCv()) {
      if ($application = $this->applyJob($nid)) {
        /** @var Node $offer */
        $offer = $this->getOfferByID($nid);
        // Check if offer require a motivation letter.
        if ($offer->get('field_exiger_lettre_motivation')->getString() == "1") {
          // Redirect to the proper form
          //kint('redirect');
          return $this->redirect('aneti_enhance.aneti_apply_controller_finsh', ['nid' => $application->id()]);
        }
      }
      drupal_set_message($this->t('You have applyed successfully to the offer'));
      return $this->redirect('view.mes_postulations.page_1');
    }
    else {
      drupal_set_message($this->t('You have to add a CV before applying'));
      return $this->redirect('view.mon_cv.page_1');
    }

  }

  public function finish($nid) {
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create(['type' => 'complement_postulation']);

    $form = \Drupal::entityTypeManager()
      ->getFormObject('node', 'default')
      ->setEntity($node);
    return \Drupal::formBuilder()->getForm($form);
  }

  protected function getOfferByID($nid) {
    if ($node = Node::load($nid)) {
      return $node;
    }
    return FALSE;
  }

  protected function userIsCandidat() {
    if (in_array('candidat', $this->currentUser()->getRoles())) {
      return TRUE;
    }
    return FALSE;
  }

  protected function getUserCv() {
    $query = $this->entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('status', 1)
      ->condition('type', 'curriculum_vitae')
      ->condition('uid', $this->currentUser()->id())
      ->execute();

    if ($query_result) {
      return array_shift($query_result);
    }
  }

  private function applyJob($jod_id) {
    $node = Node::create([
      'type'        => 'postulation',
      'title'       => $jod_id . ' - ' . $this->currentUser()->id(),
      'field_candidats' => [
        'target_id' => $this->currentUser()->id(),
      ],
      'field_intitul_de_l_offre' => [
        'target_id' => $jod_id,
      ],
      'field_candidature' => [
        'value' => 0
      ]
    ]);

    if ($node->save()) {
      return $node;
    }

    return FALSE;
  }

}
