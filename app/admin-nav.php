<?php
function admin_tabs(): array {
    $tabs=['dashboard'=>'Tổng quan','projects'=>'Sự kiện','stories'=>'Câu chuyện & bài viết','gallery'=>'Ảnh dự án','registrations'=>'Đăng ký','contacts'=>'Liên hệ'];
    if ((current_user()['role'] ?? '')==='admin') $tabs['users']='Tài khoản';
    return $tabs;
}
function admin_tab_url(string $tab): string {
    if ($tab==='projects') return 'events.php';
    if ($tab==='users') return 'users.php';
    if ($tab==='stories') return 'stories.php';
    return 'index.php?tab='.rawurlencode($tab);
}
