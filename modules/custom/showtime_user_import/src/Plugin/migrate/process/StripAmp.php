<?php

namespace Drupal\showtime_user_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin removes ampersands from first names.
 *
 * @MigrateProcessPlugin(
 *   id = "strip_amp"
 * )
 */
class StripAmp extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $string = trim($value);
    if (strpos($value, ' ') !== FALSE) {
      // Replace all non-alpha numberic characters.
      $string = preg_replace("/[^A-Za-z0-9 ]/", '.', $string);
      // Replace " and " with ".".
      $string = str_replace(' and ', '.', $string);
      // Remove space from within words. Ex: Mary Lou.
      $string = str_replace(' ', '.', $string);
    }

    return $string;
  }

}
