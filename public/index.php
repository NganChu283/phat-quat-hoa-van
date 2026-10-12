<?php
// Keep bookmarked /public/index.php URLs working after moving the homepage.
$base = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/index.php')));
$base = ($base === '/' || $base === '.') ? '' : $base;
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . $base . '/index.php' . ($query !== '' ? '?' . $query : ''), true, 301);
exit;
