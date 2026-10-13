<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/bootstrap.php';
$columns=array_column(query_all('SHOW COLUMNS FROM projects'),'Field');
if(!in_array('registration_url',$columns,true))db()->exec("ALTER TABLE projects ADD COLUMN registration_url VARCHAR(2048) NOT NULL DEFAULT ''");
echo "Event Google Form field is ready.\n";

// Set the provided workshop form only for the named event when no form is configured.
$q=db()->prepare("UPDATE projects SET registration_url=? WHERE title IN (?,?) AND registration_url=''");
$q->execute(['https://docs.google.com/forms/d/e/1FAIpQLSefY65D224sZfNwrIRwLP9AHkTk16K5e4Ecmp045WaZaAZutA/viewform','Xoè nếp xưa, đón gió lành','Chạm Ký Hồn Quê']);
