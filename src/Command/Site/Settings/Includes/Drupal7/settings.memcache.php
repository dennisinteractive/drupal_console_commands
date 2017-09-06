<?php
/**
 * Memcache settings.
 */
$conf['memcache_servers'] = ['127.0.0.1:11211' => 'default'];
$conf['memcache_bins'] = ['cache' => 'default'];
$conf['memcache_key_prefix'] = '${memcache_prefix}';
$conf['memcache_stampede_protection'] = TRUE;
