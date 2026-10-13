<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/bootstrap.php';
db()->exec(file_get_contents(__DIR__.'/../database/gallery-groups.sql'));
echo "Gallery groups are ready.\n";
