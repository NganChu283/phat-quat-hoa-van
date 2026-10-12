const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('#navigation');
const setMenu = open => {
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'Đóng danh mục' : 'Mở danh mục');
  nav.classList.toggle('open', open);
};
if (toggle && nav) {
  setMenu(false);
  toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
  nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { setMenu(false); toggle.focus(); }
  });
  matchMedia('(min-width: 801px)').addEventListener('change', e => { if (e.matches) setMenu(false); });
}
const items = [...document.querySelectorAll('.gallery-item')];
document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
  document.querySelectorAll('[data-filter]').forEach(b => { b.classList.toggle('active', b === button); b.setAttribute('aria-pressed', String(b === button)); });
  items.forEach(item => { item.hidden = button.dataset.filter !== 'all' && item.dataset.category !== button.dataset.filter; });
  document.querySelector('#gallery-empty').hidden = items.some(i => !i.hidden);
}));
const dialog = document.querySelector('#lightbox');
items.forEach(item => item.addEventListener('click', () => {
  dialog.querySelector('img').src = item.dataset.image; dialog.querySelector('img').alt = item.dataset.title;
  dialog.querySelector('p').textContent = item.dataset.title; dialog.showModal();
}));
dialog?.querySelector('.close-lightbox').addEventListener('click', () => dialog.close());
dialog?.addEventListener('click', e => { if (e.target === dialog) dialog.close(); });
document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
  const button = form.querySelector('button[type="submit"]');
  if (button) { button.disabled = true; button.textContent = 'Đang gửi…'; }
}));
if (location.hash && document.querySelector('.notice')) document.querySelector('.notice').scrollIntoView({ block: 'center' });

// Giờ bắt đầu do máy chủ xuất với UTC+7; đồng hồ không phụ thuộc múi giờ trình duyệt.
const countdown = document.querySelector('.countdown[data-target]');
if (countdown) {
  const target = Date.parse(countdown.dataset.target);
  const serverNow = Number(countdown.dataset.serverNow);
  const clockOffset = serverNow - Date.now();
  const units = [...countdown.querySelectorAll('[data-unit]')];
  const status = document.querySelector('.countdown-status');
  let timer;
  const tick = () => {
    const seconds = Math.max(0, Math.ceil((target - (Date.now() + clockOffset)) / 1000));
    const values = [Math.floor(seconds / 86400), Math.floor(seconds % 86400 / 3600), Math.floor(seconds % 3600 / 60), seconds % 60];
    units.forEach((unit, i) => { unit.textContent = String(values[i]).padStart(2, '0'); });
    if (seconds === 0) { status.textContent = 'Dự án đã đến giờ bắt đầu.'; clearInterval(timer); }
  };
  if (Number.isFinite(target) && Number.isFinite(serverNow)) {
    tick();
    if (target > serverNow) timer = setInterval(tick, 1000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) tick(); });
  }
}
