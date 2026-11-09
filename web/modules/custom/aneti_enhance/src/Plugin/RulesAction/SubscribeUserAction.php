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
 *  id = "role_user_subscribe_action",
 *  label = @Translation("Subscribe a user to a news letter"),
 *  category = @Translation("User"),
 *  context = {
 *    "user" = @ContextDefinition("entity:user",
 *      label = @Translation("User"),
 *      multiple = FALSE
 *    ),
 *    "newsletter_id" = @ContextDefinition("string",
 *      label = @Translation("Newsletter ID"),
 *      description = @Translation("The id of the newsletter."),
 *    )
 *  }
 * )
 */

class SubscribeUserAction extends RulesActionBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\simplenews\Subscription\SubscriptionManager $subscriptionManager */
  protected $subscriptionManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, SubscriptionManager $subscriptionManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscriptionManager = $subscriptionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simplenews.subscription_manager')
    );
  }

  /**
   * @inheritdoc
   */
  protected function doExecute($user, $newsletter_id) {
    /** @var \Drupal\simplenews\Subscription\SubscriptionManager $subscriptionManager */
    $this->subscriptionManager->subscribe($user->get('mail')->value, $newsletter_id, FALSE, 'auto subscribe');
  }
}
