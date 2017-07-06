<?php
// Set Stage file proxy origin.
$config['stage_file_proxy.settings']['origin'] = '${cdn}';

// Change CDN domain to local.
$config['cdn.settings']['mapping']['domain'] = '${host}';
