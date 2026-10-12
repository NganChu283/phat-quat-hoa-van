<?php
return [
    'db_host' => '127.0.0.1', 'db_port' => 3306,
    'db_name' => 'phat_quat_hoa_van', 'db_user' => 'root', 'db_password' => '',
    'site_url' => '', // URL HTTPS công khai, không có dấu / cuối
    'organizer_email' => 'phatquathoavan.stu@gmail.com',
    'mail_from' => '', 'smtp_host' => '', 'smtp_port' => 587,
    'smtp_security' => 'tls', 'smtp_username' => '', 'smtp_password' => '',
    // Chỉ dùng để tạo quản trị viên ban đầu qua php app/migrate.php. Sau đó đăng nhập dùng users.
    'admin_username' => 'admin',
    // Tạo bằng: php -r 'echo password_hash("mat-khau-cua-ban", PASSWORD_DEFAULT), PHP_EOL;'
    'admin_password_hash' => '',
];
