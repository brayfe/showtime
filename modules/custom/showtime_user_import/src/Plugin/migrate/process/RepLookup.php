<?php

namespace Drupal\showtime_user_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin removes ampersands from first names.
 *
 * @MigrateProcessPlugin(
 *   id = "rep_lookup"
 * )
 */
class RepLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $uid = 0;
    $name = strtolower($value);
    $users = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['name' => $name]);
    $user = reset($users);
    if ($user) {
      $uid = $user->id();
    }

    return $uid;
  }

}
