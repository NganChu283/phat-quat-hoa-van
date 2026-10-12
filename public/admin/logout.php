<?php
require __DIR__ . '/../../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
check_csrf(); $_SESSION = []; session_destroy(); redirect('login.php');
