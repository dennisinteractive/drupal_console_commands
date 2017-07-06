<?php
// Set Stage file proxy origin.
$conf['stage_file_proxy.settings']['origin'] = '${cdn}';

// Change CDN domain to local.
$conf['cdn.settings']['mapping']['domain'] = '${host}';
