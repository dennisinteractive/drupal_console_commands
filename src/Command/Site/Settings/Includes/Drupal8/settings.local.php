<?php
// Set Stage file proxy origin.
//$config['stage_file_proxy.settings']['origin'] = '${cdn}';
$config['stage_file_proxy.settings']['origin'] = 'cdn.drivingelectric.com';

// Change CDN domain to local.
$config['cdn.settings']['mapping']['domain'] = '${host}';
