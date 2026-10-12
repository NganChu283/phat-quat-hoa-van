<section class="event-strip" id="lich-su-kien" aria-labelledby="event-strip-title">
  <div class="event-strip-info">
    <p class="eyebrow">LỊCH SỰ KIỆN</p>
    <h2 id="event-strip-title"><?= e($project['title'] ?? 'Hẹn bạn trong một câu chuyện mới') ?></h2>
    <?php if (!empty($project['subtitle'])): ?><p class="event-strip-meta"><?= e($project['subtitle']) ?></p><?php endif; ?>
    <p class="event-strip-date"><?= $start ? 'Bắt đầu lúc '.e($start->format('H:i · d/m/Y')).' (giờ Việt Nam)' : 'Thời gian và địa điểm sẽ được ban tổ chức cập nhật.' ?></p>
    <?php if (!empty($project['ends_at'])): ?><p class="event-strip-meta">Kết thúc lúc <?= e((new DateTimeImmutable($project['ends_at']))->format('H:i · d/m/Y')) ?></p><?php endif; ?>
    <?php if (!empty($project['venue'])): ?><p class="event-strip-meta"><?= e($project['venue']) ?><?= !empty($project['address'])?' — '.e($project['address']):'' ?></p><?php endif; ?>
    <?php if (!empty($project['schedule'])): ?><details class="event-schedule"><summary>Lịch hoạt động</summary><p><?= e($project['schedule']) ?></p></details><?php endif; ?>
  </div>
  <div class="event-strip-clock">
    <div class="countdown" <?= $start ? 'data-target="'.e($start->format('c')).'" data-server-now="'.(time()*1000).'"' : '' ?> role="group" aria-label="Đếm ngược đến giờ bắt đầu dự án">
      <?php foreach (['Ngày','Giờ','Phút','Giây'] as $i=>$label): ?><div class="countdown-unit"><strong data-unit="<?= $i ?>"><?= $start ? str_pad((string)$clock[$i],2,'0',STR_PAD_LEFT) : '– –' ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
    </div>
    <p class="countdown-status" role="status"><?= $start ? ($remaining > 0 ? 'Đếm ngược đến giờ bắt đầu.' : 'Dự án đã đến giờ bắt đầu.') : 'Lịch tham gia sẽ được cập nhật.' ?></p>
    <noscript><p class="countdown-status">Bật JavaScript để đồng hồ cập nhật mỗi giây.</p></noscript>
  </div>
</section>
