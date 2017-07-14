<?php
/**
 * Memcache settings.
 */
$settings['memcache']['servers'] = ['127.0.0.1:11211' => 'default'];
$settings['memcache']['bins'] = ['default' => 'default'];
$settings['memcache']['key_prefix'] = '${memcache_prefix}';
$settings['memcache']['stampede_protection'] = TRUE;
