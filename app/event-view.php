<section class="section event-details" id="su-kien"><p class="eyebrow">THÔNG TIN SỰ KIỆN</p><h2>Hẹn gặp bạn.</h2>
<?php if (!$project): ?><p>Lịch và địa điểm sự kiện sẽ được ban tổ chức cập nhật.</p>
<?php else: ?><div class="admin-grid"><div><h3><?= e($project['title']) ?></h3><p><strong>Bắt đầu:</strong> <?= e(project_start($project)->format('H:i · d/m/Y')) ?> (UTC+7)</p>
<p><strong>Kết thúc:</strong> <?= !empty($project['ends_at']) ? e((new DateTimeImmutable($project['ends_at']))->format('H:i · d/m/Y')) : 'Đang cập nhật' ?></p>
<p><strong>Địa điểm:</strong> <?= e(($project['venue'] ?? '') ?: 'Đang cập nhật') ?><br><?= e($project['address'] ?? '') ?></p>
<?php if (!empty($project['venue']) && !empty($project['address'])): $map=$project['venue'].' '.$project['address']; ?><a class="text-link" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($map) ?>">Mở bản đồ / chỉ đường ↗</a><iframe title="Bản đồ địa điểm sự kiện" src="https://maps.google.com/maps?q=<?= rawurlencode($map) ?>&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="event-map"></iframe><?php endif; ?>
<?php if (!empty($project['ends_at']) && !empty($project['venue']) && !empty($project['address'])): ?><a class="button" href="calendar.php">Thêm vào lịch (.ics) ↗</a><?php endif; ?></div>
<div><h3>Lịch hoạt động</h3><p class="article-body"><?= e(($project['schedule'] ?? '') ?: 'Lịch chi tiết sẽ được cập nhật. Ban tổ chức sẽ xác nhận thông tin khi phản hồi đăng ký.') ?></p></div></div><?php endif; ?></section>
