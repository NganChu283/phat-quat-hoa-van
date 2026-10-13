SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS gallery_groups (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(40) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @xoe_group_count_before = (SELECT COUNT(*) FROM gallery_groups);
INSERT IGNORE INTO gallery_groups(title)
SELECT title FROM (
 SELECT 'Quạt Chàng Sơn' title UNION ALL SELECT 'Hoạt động' UNION ALL SELECT 'Chưa phân nhóm'
) initial_groups WHERE @xoe_group_count_before = 0;
INSERT IGNORE INTO gallery_groups(title)
SELECT DISTINCT category FROM gallery WHERE TRIM(category)<>'';
