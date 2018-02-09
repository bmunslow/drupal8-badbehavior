<?php

/**
 * @file
 * Contains \Drupal\badbehavior\BadBehaviorSubscriber.
 */

namespace Drupal\badbehavior\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a MyModuleSubscriber.
 */
class BadBehaviorSubscriber implements EventSubscriberInterface {

  /**
   * // only if KernelEvents::REQUEST !!!
   * @see Symfony\Component\HttpKernel\KernelEvents for details
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function BadBehaviorLoad(GetResponseEvent $event) {
    if (PHP_SAPI !== 'cli' && \Drupal::currentUser()->isAnonymous()) {
      badbehavior_start_badbehavior();
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('BadBehaviorLoad', 210);
    return $events;
  }
}