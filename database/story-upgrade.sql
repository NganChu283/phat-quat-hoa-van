-- Chọn database website trong phpMyAdmin rồi import file này.
-- Chỉ thêm cột còn thiếu, không xóa hoặc đổi nội dung đã lưu; chạy lại được.
SET @has_body_format = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='story_sections' AND COLUMN_NAME='body_format');
SET @upgrade_sql = IF(@has_body_format=0, 'ALTER TABLE story_sections ADD COLUMN body_format VARCHAR(10) NOT NULL DEFAULT ''plain''', 'SELECT ''story_sections.body_format đã có'' AS status');
PREPARE story_upgrade FROM @upgrade_sql;
EXECUTE story_upgrade;
DEALLOCATE PREPARE story_upgrade;
