<?php

namespace Drupal\unique_postulation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Prevent a candidate from applying more than once.
 *
 * @Constraint(
 *   id = "UniquePostulation",
 *   label = @Translation("prevent a candidate from applying more than once", context = "Validation"),
 *   type = "entity"
 * )
 */
class UniquePostulationConstraint extends Constraint {

  /**
   * Message shown when trying create postulation.
   *
   * @var string
   */
  public $message = 'Applied';

  /**
   * {@inheritdoc}
   */
}
