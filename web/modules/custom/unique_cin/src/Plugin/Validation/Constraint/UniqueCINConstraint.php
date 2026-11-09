<?php

namespace Drupal\unique_cin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * CIN MUST BE UNIQUE.
 *
 * @Constraint(
 *   id = "UniqueCIN",
 *   label = @Translation("CIN must be unique", context = "Validation"),
 *   type = "entity"
 * )
 */
class UniqueCINConstraint extends Constraint {

  /**
   * Message shown when trying create profile candidat.
   *
   * @var string
   */
  public $message = 'CIN must be unique';

  /**
   * {@inheritdoc}
   */
}
