<?php

namespace Drupal\sms\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;

/**
 * Class SmsService
 * @package Drupal\sms\Services
 */

class SmsService {

  /**
   * The currentUser Obj.
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * SmsService constructor.
   * @param AccountInterface $currentUser
   */
  public function __construct(AccountInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * Get current user object.
   */
  public function getCurrentUser() {
    $user = $this->currentUser;
    return $user;
  }
}