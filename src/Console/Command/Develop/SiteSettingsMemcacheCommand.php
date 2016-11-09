<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteSettingsMemcacheCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteSettingsMemcacheCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteSettingsMemcacheCommand extends SiteBaseCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'settings.memcache.php';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site:settings:memcache')
      // @todo use: ->setDescription($this->trans('commands.site.settings.memcache.description'))
      ->setDescription('Generates settings.memcache.php for a given site.')
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.settings.memcache.site-name.description')
        'The site name that is mapped to a repo in sites.yml'
      )->addOption(
        'destination-directory',
        '-D',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.memcache.site-name.options')
        'Specify the destination of the site settings.memcache.php if different than the global destination found in sites.yml'
      )->addOption(
        'prefix',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.memcache.prefix')
        'Memcache key prefix. [default: site machine name from sites.yml]'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    if (!file_exists($this->destination . 'settings.php')) {
      $message = sprintf('The file settings.php is missing on %s',
        $this->destination
      );
      throw new SiteCommandException($message);
    }

    if (is_null($input->getOption('prefix'))) {
      $input->setOption('prefix',  $this->siteName);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    // Remove existing file.
    $file = $this->destination . $this->filename;
    if (file_exists($file)) {
      unlink($file);
    }

    // Prepare content.
    $memcache_prefix = $input->getOption('prefix');

    $content = <<<EOF
<?php
//@TODO These memcache settings are for D7.
/**
 * Memcache Settings.
 */
include_once('./includes/cache.inc');
include_once('./profiles/dennis_distro/modules/contrib/memcache/memcache.inc');

// uncomment the line below to disable caching
// \$conf['cachestatus'] = 1;

if ((isset(\$conf['cachestatus'])) && (\$conf['cachestatus'] > 0)) {
  \$conf['cache_backends'][] = 'includes/cache-install.inc';
  // Default to throwing away cache data
  \$conf['cache_default_class'] = 'DrupalFakeCache';
  // Rely on the DB cache for form caching - otherwise forms fail.
  \$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';
  // Rely on the external cache for page caching.
  \$conf['cache_class_cache_page'] = 'DrupalFakeCache';
}
else {
  \$conf['cache_default_class'] = 'MemCacheDrupal';
  \$conf['memcache_key_prefix'] = '$memcache_prefix';
  \$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';
  \$conf['cache_class_cache_update'] ='DrupalDatabaseCache';

  \$conf['memcache_servers'] = array(
    '127.0.0.1:11211' => 'default',
    '127.0.0.1:11212' => 'menu',
    '127.0.0.1:11213' => 'filter',
    '127.0.0.1:11214' => 'form',
    '127.0.0.1:11215' => 'block',
    '127.0.0.1:11216' => 'update',
    '127.0.0.1:11217' => 'views',
    '127.0.0.1:11218' => 'viewsdata',
    '127.0.0.1:11219' => 'apachesolr',
    '127.0.0.1:11220' => 'path',
    '127.0.0.1:11221' => 'field',
    '127.0.0.1:11222' => 'rules',
    '127.0.0.1:11223' => 'token',
    '127.0.0.1:11224' => 'image',
  );

  \$conf['memcache_bins'] = array(
    'cache' => 'default',
    'cache_menu'   => 'menu',
    'cache_filter' => 'filter',
    'cache_form'   => 'form',
    'cache_block'  => 'block',
    'cache_update' => 'update',
    'cache_views'  => 'views',
    'cache_views_data'  => 'viewsdata',
    'cache_apachesolr'  => 'apachesolr',
    'cache_path'  => 'path',
    'cache_field'  => 'field',
    'cache_rules'  => 'rules',
    'cache_token'  => 'token',
    'cache_image'  => 'image',
  );
}
EOF;

    file_put_contents($file, $content);

    // Check file.
    if (file_exists($file)) {
      $this->io->success(sprintf('Generated %s',
        $file)
      );
    }
    else {
      throw new SiteCommandException('Error generating %s',
        $file
      );
    }
  }
}
