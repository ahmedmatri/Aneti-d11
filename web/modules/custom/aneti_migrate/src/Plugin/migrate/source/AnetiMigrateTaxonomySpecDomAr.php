<?php

namespace Drupal\aneti_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

//use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\taxonomy\Plugin\migrate\source\d7\Term;

/**
 * Provides a 'AnetiMigrateTaxonomyPaysEn' migrate source.
 *
 * @MigrateSource(
 *  id = "aneti_migrate_taxonomy_spec_dom_ar"
 * )
 */
class AnetiMigrateTaxonomySpecDomAr extends Term {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_data', 't')
      //->fields('t1')
      ->distinct()
      ->orderBy('tid');
    $query->leftJoin('taxonomy_term_data', 't1', '(t.i18n_tsid = t1.i18n_tsid AND t.tid <> t1.tid AND t1.language=\'fr\')');
    $query->addField('t', 'name');
    $query->addField('t1', 'tid');
    $query->addField('t', 'language');
    $query->condition('t.language', 'ar', '=');
    $query->condition('t.vid', 11, '=');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('tid'),
      'name' => $this->t('name')
    ];
    return $fields;
  }

  public function prepareRow(Row $row) {
    return [];
  }
}
