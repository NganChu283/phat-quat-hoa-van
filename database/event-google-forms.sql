-- Select the website database in phpMyAdmin before importing.
-- Adds the registration URL field only when it is missing; existing data is preserved.
SET @xoe_google_forms_sql = (
    SELECT IF(COUNT(*)=0,
        'ALTER TABLE projects ADD COLUMN registration_url VARCHAR(2048) NOT NULL DEFAULT ''''',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='projects' AND COLUMN_NAME='registration_url'
);
PREPARE xoe_google_forms_update FROM @xoe_google_forms_sql;
EXECUTE xoe_google_forms_update;
DEALLOCATE PREPARE xoe_google_forms_update;

SET NAMES utf8mb4;
UPDATE projects
SET registration_url = 'https://docs.google.com/forms/d/e/1FAIpQLSefY65D224sZfNwrIRwLP9AHkTk16K5e4Ecmp045WaZaAZutA/viewform'
WHERE title IN ('Xoè nếp xưa, đón gió lành', 'Chạm Ký Hồn Quê')
  AND registration_url = '';
