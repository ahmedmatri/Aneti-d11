<?php

namespace Drupal\aneti_enhance\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\simplenews\Subscription\SubscriptionManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RoleEmailAction' action.
 *
 * @RulesAction(
 *  id = "aneti_debug_action",
 *  label = @Translation("Debug a value"),
 *  category = @Translation("Debug"),
 *  context = {
 *    "data" = @ContextDefinition("any",
 *      label = @Translation("Data"),
 *      multiple = FALSE
 *    )
 *  }
 * )
 */

class DebugAction extends RulesActionBase {

  /**
   * @inheritdoc
   */
  protected function doExecute($data) {
    if (function_exists('kint')) {
      kint($data);die;
    }
    else {
      var_dump($data);
    }
  }
}
