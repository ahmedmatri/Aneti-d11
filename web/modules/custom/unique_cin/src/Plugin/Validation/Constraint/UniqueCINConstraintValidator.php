<?php

namespace Drupal\unique_cin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ArticleLimit constraint.
 */
class UniqueCINConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }
		

		if($entity->getEntityTypeId()==="profile" && $entity->bundle() === "candidat" ){
			if ($entity->isNew()) {
				$cin=$entity->get('field_cin')->getString();
				$cin_1=intval(substr($cin, 0, 1));
				$perm_1=array(0,1);
				$r_cin=array('00000000','11111111','22222222','33333333','44444444','55555555','66666666','77777777','88888888','99999999',
										'00000000','01111111','02222222','03333333','04444444','05555555','06666666','07777777','08888888','09999999',
										'10000000','11111111','12222222','13333333','14444444','15555555','16666666','17777777','18888888','19999999'
				);
				//$type=$entity->getEntityTypeId();
				//$type=$entity->bundle();
				if(strlen($cin) != 8 || !is_numeric($cin) || !in_array($cin_1,$perm_1) || in_array($cin,$r_cin)){
					$this->context->addViolation(t('Incorrect CIN format'));
				}			
								
			  $query = \Drupal::entityTypeManager()->getStorage('profile');
			  $query_result = $query->getQuery()
						->condition('field_cin', $cin)
						->condition('type', 'candidat')
						->accessCheck(FALSE)
						->count()
						->execute();

			  if ($query_result>0) {
			      $this->context->addViolation($constraint->message);
			  }
			}
		}
  }

}
