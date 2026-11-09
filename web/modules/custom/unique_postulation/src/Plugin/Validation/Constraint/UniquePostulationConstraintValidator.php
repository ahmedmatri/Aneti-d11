<?php

namespace Drupal\unique_postulation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniquePostulation constraint.
 */
class UniquePostulationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    // If the entity already has an id we're in an entity *update* operation
    // instead of an entity *creation* operation. We don't want prevent
    // existing postulations from being updated.
    if ($entity->id()) {
      return;
    }

    if ($entity->bundle() == 'postulation') {
      // Hardcoded for simplicity.
      $off=$entity->get('field_intitul_de_l_offre')->target_id;
	    $query = \Drupal::entityTypeManager()->getStorage('node');
	    $query_result = $query->getQuery()
	      ->condition('field_candidats', \Drupal::currentUser()->id())
	      ->condition('field_intitul_de_l_offre', $off)
	      ->condition('type', 'postulation')
	      ->execute();

	    if ($query_result) {
	      $this->context->addViolation($constraint->message);
	    }

    }
  }

}
