<?php
require __DIR__.'/../app/bootstrap.php';
// Keep old FAQ links working after removing the page.
header('Location: '.site_path('index.php'), true, 301);
exit;
