<?php
$visibleIntros=$storyIntros;
if (!$visibleIntros && ($storySections || $storyPosts)) $visibleIntros=[null];
?>
<?php if ($visibleIntros): ?><div id="cau-chuyen" class="story-group">
<?php foreach ($visibleIntros as $introIndex=>$story): ?>
<section class="story section <?= !$story?'story-without-intro':'' ?>" aria-labelledby="story-title-<?= $introIndex ?>">
<div class="story-copy">
<?php if ($story): ?><p class="eyebrow"><?= e($story['eyebrow']) ?></p><h2 id="story-title-<?= $introIndex ?>"><?= e($story['title']) ?><?php if ($story['title_accent']): ?><br><em><?= e($story['title_accent']) ?></em><?php endif; ?></h2><p class="lead"><?= e($story['intro']) ?></p>
<?php else: ?><h2 id="story-title-<?= $introIndex ?>">Câu chuyện</h2><?php endif; ?>
<?php if ($introIndex===0): foreach ($storySections as $section): ?><details><summary><?= e($section['title']) ?> <span aria-hidden="true">+</span></summary><div class="rich-article story-detail-body"><?= post_body($section) ?></div></details><?php endforeach; endif; ?></div>
<?php if ($story): ?><figure class="story-image"><?= image_tag($story['image'],$story['image_alt'],'loading="lazy"','(max-width: 800px) 88vw, 40vw') ?><figcaption><?= e($story['image_caption']) ?></figcaption></figure><?php endif; ?>
<?php if ($storyPosts && $introIndex===count($visibleIntros)-1): ?><div class="story-posts"><div class="story-posts-heading"><h3>Đọc thêm câu chuyện</h3><a class="text-link" href="posts.php">Xem tất cả bài viết ↗</a></div><div class="post-grid">
<?php foreach ($storyPosts as $post): ?><article class="post"><a href="post.php?id=<?= (int)$post['id'] ?>"><?= image_tag($post['image'],$post['title'],'loading="lazy"','(max-width: 450px) 88vw, (max-width: 800px) 42vw, 28vw') ?><h3><?= e($post['title']) ?></h3><p><?= e($post['excerpt']) ?></p><span class="story-read-button">Đọc câu chuyện <span aria-hidden="true">↗</span></span></a></article><?php endforeach; ?></div></div><?php endif; ?>
</section>

<?php endforeach; ?></div><?php endif; ?>
