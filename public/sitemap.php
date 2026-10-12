<?php
require __DIR__.'/../app/bootstrap.php'; 
try { $posts=query_all('SELECT id,updated_at FROM posts WHERE published=1'); }
catch (PDOException $ex) { http_response_code(503); exit; }
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach (['index.php','posts.php','faq.php','partners.php'] as $path) echo '<url><loc>'.e(site_url($path)).'</loc></url>';
foreach ($posts as $post) echo '<url><loc>'.e(site_url('post.php?id='.(int)$post['id'])).'</loc><lastmod>'.e((new DateTimeImmutable($post['updated_at']))->format('c')).'</lastmod></url>';
echo '</urlset>';
