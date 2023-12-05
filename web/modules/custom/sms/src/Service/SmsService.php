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
   * The database connection.
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * SmsService constructor.
   * @param AccountInterface $currentUser
   */
  public function __construct(AccountInterface $currentUser, Connection $connection) {
    $this->currentUser = $currentUser;
    $this->database = $connection;
  }

  /**
   * Get current user object.
   */
  public function getCurrentUser() {
    $user = $this->currentUser;
    return $user;
  }

  /**
   * Get vocabulary all terms. 
   */
  public function getAllVocabularyTerms($vocabulary) {
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(
      $vocabulary,      // The taxonomy term vocabulary machine name.
      0,                // The "tid" of parent using "0" to get all.
      1,                // Get only 1st level.
      TRUE              // Get full load of taxonomy term entity.
    );
    $results = [];
 
    foreach ($tree as $term) {
      $results[$term->id()] = $term->getName();
    }
    return $results;
  }
  
}