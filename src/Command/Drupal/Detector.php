<?php

namespace DennisDigital\Drupal\Console\Command\Drupal;

/**
 * Drupal detector.
 */
class Detector {

  /**
   * Stores the drupal version.
   */
  protected $version;

  /**
   * Stores the drupal root.
   */
  //protected $drupalRoot;

  /**
   * Constructor.
   */
  public function __construct()
  {

  }

  /**
   * Determine major Drupal version.
   *
   * @return int
   *   The major Drupal version.
   *
   * @see drush_drupal_version()
   */
  public function getDrupalVersion($drupalRoot) {
    if (!file_exists($drupalRoot)) {
      return;
    }

    $version = '';

    if (!isset($this->version)) {
      /**
       * Test possible Drupal core directories.
       * Examples:
       *   Drupal 7 using make files
       *     root: /var/www/sites/drupal/docroot_d7-example/sites/example
       *   Drupal 7 using composer
       *     root: /var/www/sites/drupal/docroot_d7-example
       *   Drupal 8
       *     root: /var/www/sites/drupal/docroot_d8-example
       */
      $version_constant_paths = array();
      $version_constant_paths['d7_makefile'] = $drupalRoot . 'includes/bootstrap.inc';
      $version_constant_paths['d7_composer'] = realpath($drupalRoot . '../../') . '/includes/bootstrap.inc';
      //$version_constant_paths['d8_composer'] = $drupalRoot . 'autoload.php';
      $version_constant_paths['d8_composer'] = $drupalRoot . 'core/includes/bootstrap.inc';

      foreach ($version_constant_paths as $file) {
        if (file_exists($file)) {
          require_once $file;

          continue;
        }
      }

      if (defined('VERSION')) {
        $version = VERSION;
      }
      elseif (defined('\Drupal::VERSION')) {
        $version = \Drupal::VERSION;
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        $this->version = (int) $version_parts[0];

        return $this->version;
      }
    }
  }
}
