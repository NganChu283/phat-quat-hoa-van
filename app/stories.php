<?php
function default_story_settings(): array {
    return ['id'=>1,'eyebrow'=>'01 / CÂU CHUYỆN CHÀNG SƠN','title'=>'Ký ức mở ra','title_accent'=>'từ một nan quạt.','intro'=>'Chiếc quạt không chỉ mang làn gió mát. Trong từng nếp gấp còn có câu chuyện về bàn tay, chất liệu và ký ức làng nghề.','image'=>'assets/photos/carved-bamboo-fan.webp','image_alt'=>'Đôi tay cầm chiếc quạt với họa tiết chạm khắc trên nan tre','image_caption'=>'Chàng Sơn · Nét chạm trong từng nan quạt'];
}
function default_story_sections(): array {
    return [
        ['title'=>'Lịch sử hình thành','body'=>'Theo nội dung dự án Phất Quạt Họa Văn, làng nghề quạt Chàng Sơn có lịch sử hơn 200 năm, hình thành từ thế kỷ XIX. Nghề làm quạt được người dân giữ gìn và truyền dạy qua nhiều thế hệ.','body_format'=>'plain','published'=>1,'sort_order'=>10],
        ['title'=>'Phất Quạt Họa Văn','body'=>"Dự án được tổ chức bởi nhóm sinh viên năm 3 lớp Truyền thông Marketing K43A1, thuộc Khoa Quan hệ Công chúng & Quảng cáo — Học viện Báo chí và Tuyên truyền.\n\nDự án giới thiệu và lan tỏa giá trị của làng nghề quạt Chàng Sơn tới cộng đồng.",'body_format'=>'plain','published'=>1,'sort_order'=>20],
    ];
}
