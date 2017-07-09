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
    $string = $value;

    if (strpos($value, '&') !== FALSE) {
      $result = explode('&', $value);
      $result = array_map('trim', $result);
      // Remove space from within words. Ex: Mary Lou.
      $string = implode(".", $result);
    }

    return $string;
  }

}
