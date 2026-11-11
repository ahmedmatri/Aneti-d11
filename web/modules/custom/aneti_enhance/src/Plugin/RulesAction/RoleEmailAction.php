<?php

namespace Drupal\aneti_enhance\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'RoleEmailAction' action to send emails to users with specific roles.
 *
 * @RulesAction(
 *   id = "role_email_action",
 *   label = @Translation("Send email to users of a role"),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "roles" = @ContextDefinition("string",
 *       label = @Translation("Roles"),
 *       description = @Translation("The roles whose users will receive the notification."),
 *       multiple = TRUE
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       description = @Translation("Email subject.")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("Email message content.")
 *     ),
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Context node if applicable."),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class RoleEmailAction extends RulesActionBase {

  /**
   * Executes the action.
   *
   * @param array $roles
   *   Roles to notify.
   * @param string $subject
   *   Email subject.
   * @param string $message
   *   Email body.
   * @param \Drupal\node\NodeInterface|null $node
   *   Node object.
   */
  protected function doExecute(array $roles, $subject, $message, $node = NULL) {
    if (function_exists('aneti_enhance_notify_roles')) {
      aneti_enhance_notify_roles($roles, $subject, $message);
    }
  }

}

