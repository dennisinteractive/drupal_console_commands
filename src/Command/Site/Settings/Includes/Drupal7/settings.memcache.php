<?php
/**
 * Memcache settings.
 */
$conf['memcache']['servers'] = ['127.0.0.1:11211' => 'default'];
$conf['memcache']['bins'] = ['default' => 'default'];
$conf['memcache']['key_prefix'] = '${memcache_prefix}';
$conf['memcache']['stampede_protection'] = TRUE;
