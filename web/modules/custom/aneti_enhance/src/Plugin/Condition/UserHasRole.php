<?php

namespace Drupal\aneti_enhance\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
//use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\user\UserInterface;

/**
 * Provides a 'User has roles(s)' condition.
 *
 * @Condition(
 *   id = "custom_rules_user_has_role",
 *   label = @Translation("User has role (custom)"),
 *   category = @Translation("User"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     ),
 *     "roles" = @ContextDefinition("string",
 *       label = @Translation("Roles"),
 *       multiple = FALSE
 *     )
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 */
class UserHasRole extends RulesConditionBase {

  /**
   * Evaluate if user has role(s).
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to check.
   * @param \Drupal\user\RoleInterface[] $roles
   *   Array of user roles.
   * @param string $operation
   *   Either "AND": user has all of roles.
   *   Or "OR": user has at least one of all roles.
   *   Defaults to "AND".
   *
   * @return bool
   *   TRUE if the user has the role(s).
   */
  protected function doEvaluate(UserInterface $account, $roles, $operation = 'AND') {
    return in_array($roles,$account->getRoles());
  }

}
