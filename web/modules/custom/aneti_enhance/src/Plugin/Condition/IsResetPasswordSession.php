<?php

namespace Drupal\aneti_enhance\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
//use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\user\UserInterface;

/**
 * Provides a 'User has roles(s)' condition.
 *
 * @Condition(
 *   id = "custom_rules_is_reset_password_session",
 *   label = @Translation("Is reset password Session (custom)"),
 *   category = @Translation("User"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     )
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 */
class IsResetPasswordSession extends RulesConditionBase {

  /**
   * Evaluate if it's as reset password session.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to check.
   * @param string $operation
   *   Either "AND": user has all of roles.
   *   Or "OR": user has at least one of all roles.
   *   Defaults to "AND".
   *
   * @return bool
   *   TRUE if it's a reset password session.
   */
  protected function doEvaluate(UserInterface $account, $operation = 'AND') {
    // Find the "user/reset/" string in the url.
    if (strpos(\Drupal::request()->getPathInfo(), 'user/reset/')) {
      return TRUE;
    }
    return FALSE;
  }

}
