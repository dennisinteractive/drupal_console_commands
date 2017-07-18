<?php
// Set Stage file proxy origin.
$conf['stage_file_proxy_origin'] = '${cdn}';

// Change CDN domain to local.
$conf['cdn_basic_mapping'] = '${host}|.css .js .jpg .jpeg .png';
