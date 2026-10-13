-- Select the website database in phpMyAdmin before running this update.
SET NAMES utf8mb4;
UPDATE projects
SET title = 'Xoè nếp xưa, đón gió lành'
WHERE TRIM(title) IN ('Chạm Ký Hồn Quê', 'Chạm Kí Hồn Quê');