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
  protected $drupalRoot;

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
    $this->drupalRoot = $drupalRoot;

    if (!isset($this->version)) {
      // Support 6, 7 and 8.
      $version_constant_paths = array(
        // Drupal 7.
        $this->drupalRoot . 'includes/bootstrap.inc',
        explode('sites', $drupalRoot)[0] . 'includes/bootstrap.inc',
        // Drupal 8.
        $this->drupalRoot . 'autoload.php',
        $this->drupalRoot . 'core/includes/bootstrap.inc',
      );

      foreach ($version_constant_paths as $file) {
        if (file_exists($file)) {
          require_once $file;
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
