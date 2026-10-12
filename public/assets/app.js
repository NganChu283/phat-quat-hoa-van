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
document.querySelectorAll('form[action="submit.php"]').forEach(form => form.addEventListener('submit', () => {
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

;
(() => {
  const container = document.querySelector('[data-rich-editor]');
  if (!container) return;
  const source = container.querySelector('textarea[name="body"]');
  const editor = container.querySelector('[contenteditable]');
  const toolbar = container.querySelector('[role="toolbar"]');
  const format = container.querySelector('input[name="body_format"]');
  const status = container.querySelector('[data-editor-status]');
  source.hidden = true; editor.hidden = false; toolbar.hidden = false; format.value = 'html';
  let selection;
  const remember = () => {
    const selected = window.getSelection();
    if (selected.rangeCount && editor.contains(selected.anchorNode)) selection = selected.getRangeAt(0).cloneRange();
  };
  document.addEventListener('selectionchange', remember);
  const sync = () => { source.value = editor.innerHTML; status.textContent = `${editor.innerText.trim().length.toLocaleString('vi-VN')} ký tự`; };
  editor.addEventListener('input', sync);
  toolbar.querySelectorAll('button').forEach(button => {
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', () => {
      editor.focus();
      if (selection) { const selected = window.getSelection(); selected.removeAllRanges(); selected.addRange(selection); }
      const command = button.dataset.command;
      let value = button.dataset.value || null;
      if (command === 'createLink') {
        value = window.prompt('Đường dẫn liên kết (https://… hoặc mailto:…)');
        if (!value) return;
        if (!/^(https?:\/\/|mailto:)/i.test(value.trim()) || /[\x00-\x20\x7f]/.test(value.trim())) {
          status.textContent = 'Vui lòng nhập liên kết HTTPS, HTTP hoặc email hợp lệ.'; return;
        }
      }
      document.execCommand(command, false, value); remember(); sync();
    });
  });
  // Pasted rich HTML is converted to text; formatting can be applied with the toolbar.
  editor.addEventListener('paste', event => {
    event.preventDefault(); document.execCommand('insertText', false, event.clipboardData.getData('text/plain')); sync();
  });
  container.closest('form').addEventListener('submit', event => {
    sync();
    if (!editor.innerText.replace(/\u00a0/g, ' ').trim()) { event.preventDefault(); status.textContent = 'Vui lòng nhập nội dung bài viết.'; editor.focus(); }
    else if (source.value.length > 60000) { event.preventDefault(); status.textContent = 'Nội dung vượt quá độ dài cho phép.'; }
  });
  sync();
})();
