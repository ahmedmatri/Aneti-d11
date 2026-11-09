<?php

namespace Drupal\aneti_cv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\taxonomy\Entity\Term;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\aneti_cv\AnetiTemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class EuropassController.
 */
class EuropassController extends ControllerBase {

  /**
   * Drupal\aneti_cv\AnetiTemplateProcessor definition.
   *
   * @var \Drupal\aneti_cv\AnetiTemplateProcessor
   */
  protected $anetiCvTemplateProcessor;

  protected $fileName;

  protected $resultPath;

  /**
   * Constructs a new EuropassController object.
   */
  public function __construct(AnetiTemplateProcessor $aneti_cv_template_processor) {
    $this->anetiCvTemplateProcessor = $aneti_cv_template_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aneti_cv.template_processor')
    );
  }

  /**
   * The default controller.
   *
   * @param $nid
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   * @throws \Exception
   */
  public function index($nid) {
    $node = node_load($nid);

    // Early return if the node type is not correct.
    if ($node->getType() != 'curriculum_vitae') {
      throw new NotFoundHttpException();
    }

    $tempDir = file_directory_temp();
    // The fileName uses the changed time from node. This is used to cache
    // the created file.
    $this->fileName = "CV_EURO_" . $node->getChangedTime() . "_" . $node->id() . ".docx";
    $this->resultPath = "$tempDir/" . $this->fileName;

    // If file exists, no need to rebuild it.
    if (file_exists($this->resultPath)) {
      return $this->sendResponse();
    }

    // Get the template file path.
    $templatePath = drupal_get_path('module', 'aneti_cv');
    $templatePath .= "/assets/tpl.docx";

    if (!file_exists($templatePath)) {
      throw new \Exception("Template CV file not found.");
    }

    // Load the data, fill in the doc and send the file.
    if ($data = $this->getCvData($node)) {
      try {
        $this->anetiCvTemplateProcessor->init($templatePath);

        foreach ($data as $token => $value) {
          $this->anetiCvTemplateProcessor->setValue($token, $value);
        }

        $this->anetiCvTemplateProcessor->saveAs($this->resultPath);
        return $this->sendResponse();
      }
      catch (CreateTemporaryFileException $e) {
        \Drupal::logger('aneti_cv')->error($e->getMessage());
      }
      catch (CopyFileException $e) {
        \Drupal::logger('aneti_cv')->error($e->getMessage());
      }
      catch (\Exception $e) {
        \Drupal::logger('aneti_cv')->error($e->getMessage());
      }
    }
  }

  /**
   * Returns a response object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  protected function sendResponse() {
    $response = new BinaryFileResponse($this->resultPath);
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $this->fileName
    );

    return $response;
  }

  /**
   * Gets data from various entities in order to fill in ten CV.
   *
   * @param $node
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getCvData($node) {
    // Get the user (candidat);
    //@todo: fix cv with multiple revisions
    // To repoduce the bug see any of the migrated cvs.
    $user_id =$node->get('field_candidat')->getString();
    if (!$user_id) {
      throw new \Exception("Field candidat can't be empty");
    }
    $candidat = user_load($user_id);

    $profile = \Drupal::entityTypeManager()
      ->getStorage('profile')
      ->loadByProperties([
        'uid' => $candidat->id(),
        'type' => 'candidat',
      ]);

    if (!empty($profile)) {
      $profile = reset($profile);
    }

    $data = [];

    // Nom & prenom
    $first_name = $profile->get('field_prenom')->getString();
    $last_name = $profile->get('field_nom')->getString();
    $data ['name'] = "$first_name $last_name";

    // field_adresse
    $address = $profile->get('field_adresse')->getString();

    if ($ville = $profile->get('field_ville')->getString()) {
      $ville = Term::load($ville)->getName();

      $address .= " $ville";
    }

    if ($ville_tn = $profile->get('field_ville_tunisie')->getString()) {
      $ville_tn = Term::load($ville_tn)->getName();
      $address .= " $ville_tn";
    }

    if ($code_postal = $profile->get('field_code_postal')->getString()) {
      $address .= " $code_postal";
    }

    if ($pays = $profile->get('field_pays')->getString()) {
      $pays = Term::load($pays)->getName();
      $address .= " $pays";
    }

    $data['address'] = $address;
    // tel
    $data['tel'] = $profile->get('field_telephone')->getString();

    // mobile
    $data['mobile'] = $data['tel'];

    // gender
    $data['gender'] = $profile->get('field_genre')->getString();
    // b_date

    $date = "";
    if ($profile->get('field_date_de_naissance')->getString()) {
      $timestamp = $profile->field_date_de_naissance->date->getTimestamp();
      $date = format_date($timestamp, $type = 'custom', $format = 'd/m/Y', $timezone = NULL, $langcode = NULL);
    }
    $data['b_date'] = $date;

    // email from user

    $data['email'] = $candidat->getEmail();

    // skype_id from cv
    $skype = $node->get('field_pseudo_skype')->getString();
    $data['skype_id'] = $skype;

    // Domaine de profession
    $data['field_domaine_de_profession'] = "";
    if ($domaine_de_prof = $node->get('field_domaine_de_profession')->getString()) {
      $domaine_de_prof = Term::load($domaine_de_prof)->getName();
      $data['field_domaine_de_profession'] = $domaine_de_prof;
    }

    // Profession
    $data['profession'] = "";
    if ($prof = $node->get('field_profession')->getString()) {
      $prof = Term::load($prof)->getName();
      $data['profession'] = $prof;
    }

    $date = "";
    if ($node->get('field_date_diplome')->getString()) {
      $field_date_diplome = $node->field_date_diplome->date->getTimestamp();
      $date = format_date($field_date_diplome, $type = 'custom', $format = 'd/m/Y', $timezone = NULL, $langcode = NULL);
    }
    $data['date_diplome'] = $date;

    // Diplome
    $data['diplome'] = "";
    if ($diplome = $node->get('field_diplome')->getString()) {
      $diplome = Term::load($diplome)->getName();
      $data['diplome'] = $diplome;
    }

    $field_langue_maternelle = $node->get('field_langue_maternelle')->getString();
    if ($field_langue_maternelle) {
      $field_langue_maternelle = Term::load($field_langue_maternelle)->getName();
    }
    $data['field_langue_maternelle'] = $field_langue_maternelle;

    // Do some fields to tokens mapping.
    $fields = [
      'langue' => 'lang',
      'langue2' => 'lang2',
      'langue3' => 'lang3',
      'langue4' => 'lang4',
      'langue5' => 'lang5',
      'niveau_ecrit_parle' => 'niv',
      'niveau_langue_2_ecrit_parl' => 'niv2',
      'niveau_langue_3_ecrit_parl' => 'niv3',
      'niveau_langue_4_ecrit_parl' => 'niv4',
      'niveau_langue_5_ecrit_parl' => 'niv5'
    ];

    // The level of the language.
    $niv = [
      0 => $this->t('Medium'),
      1 => $this->t('Good'),
      2 => $this->t('Fluent'),
    ];

    foreach ($fields as $field => $token) {
      // get the value from the target_id.
      $value = $node->get("field_$field")->getString();
      if ($value && strpos($token, 'ang')) {
        // Get the term name from the given target_id.
        $value = Term::load($value)->getName();
      }
      elseif (!empty($value) && in_array($value, [0, 1 , 2])) {
        $value = $niv[$value];
      }
      else {
        $data[$token] = "";
      }
      // Append the data array.
      $data[$token] = $value;
    }

    return $data;
  }
}
