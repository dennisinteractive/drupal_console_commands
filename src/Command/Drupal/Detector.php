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
        '/includes/bootstrap.inc',
        // Drupal 8.
        '/autoload.php',
        '/core/includes/bootstrap.inc',
      );

      foreach ($version_constant_paths as $path) {
        if (file_exists($this->drupalRoot . $path)) {
          require_once $this->drupalRoot . $path;
        }
      }

      if (defined('VERSION')) {
        $version = VERSION;
      }
      elseif (defined('\Drupal::VERSION')) {
        $version = \Drupal::VERSION;
      }
      else {
        // Could not find core. One last try.
        // A bit magic but that is it for now.
        $result = explode('/sites/', $drupalRoot);
        if (is_array($result)) {
          $version = $this->getDrupalVersion($result[0]);
        }
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        $this->version = (integer) $version_parts[0];
      }
      else {
        throw new \Exception(sprintf('Unable to extract major Drupal core version from version string %s.', $version));
      }
    }

    return (int) $this->version;
  }
}
