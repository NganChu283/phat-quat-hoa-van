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
document.querySelectorAll('[data-rich-editor]').forEach(container => {
  const source = container.querySelector('textarea[name="body"]');
  const editor = container.querySelector('[contenteditable]');
  const toolbar = container.querySelector('[role="toolbar"]');
  const format = container.querySelector('input[name="body_format"]');
  const status = container.querySelector('[data-editor-status]');
  const form = container.closest('form');
  const picker = container.querySelector('[data-editor-image]');
  const tablePicker = container.querySelector('[data-table-picker]');
  const tableTools = container.querySelector('[data-table-tools]');
  source.hidden = true; editor.hidden = false; toolbar.hidden = false; format.value = 'html';
  let selection = null, insertionRange = null, uploading = false;
  const selectedCell = () => {
    const node = selection?.startContainer;
    const cell = (node?.nodeType === Node.ELEMENT_NODE ? node : node?.parentElement)?.closest('td,th');
    return cell && editor.contains(cell) ? cell : null;
  };
  const updateTools = () => {
    tableTools.hidden = !selectedCell();
    ['bold','italic','underline','strikeThrough','superscript','subscript'].forEach(command => {
      toolbar.querySelector(`[data-command="${command}"]`).setAttribute('aria-pressed', String(document.queryCommandState(command)));
    });
  };
  const remember = () => {
    const selected = window.getSelection();
    if (selected.rangeCount && editor.contains(selected.anchorNode) && editor.contains(selected.focusNode)) {
      selection = selected.getRangeAt(0).cloneRange(); updateTools();
    }
  };
  const restore = (saved = selection) => {
    editor.focus();
    const selected = window.getSelection();
    if (saved && editor.contains(saved.startContainer) && editor.contains(saved.endContainer)) {
      selected.removeAllRanges(); selected.addRange(saved);
    } else {
      const range = document.createRange(); range.selectNodeContents(editor); range.collapse(false);
      selected.removeAllRanges(); selected.addRange(range);
    }
  };
  const sync = () => {
    source.value = editor.innerHTML;
    status.textContent = `${editor.innerText.trim().length.toLocaleString('vi-VN')} ký tự`;
  };
  const command = (name, value = null) => {
    restore();
    document.execCommand('styleWithCSS', false, !['fontSize','fontName'].includes(name));
    document.execCommand(name, false, value);
    remember(); sync();
  };
  document.addEventListener('selectionchange', remember);
  editor.addEventListener('input', () => { remember(); sync(); });
  toolbar.querySelectorAll('button').forEach(button => {
    button.addEventListener('mousedown', event => event.preventDefault());
  });
  toolbar.querySelectorAll('select,input[type="color"]').forEach(control => {
    control.addEventListener('pointerdown', remember);
    control.addEventListener('change', () => command(control.dataset.command, control.value));
  });
  toolbar.querySelectorAll('button[data-command]').forEach(button => {
    button.addEventListener('click', () => {
      const name = button.dataset.command;
      if (name === 'insertImage') {
        insertionRange = selection?.cloneRange(); picker.value = ''; picker.click(); return;
      }
      if (name === 'insertTable') {
        insertionRange = selection?.cloneRange(); tablePicker.hidden = !tablePicker.hidden;
        if (!tablePicker.hidden) container.querySelector('[data-table-rows]').focus();
        return;
      }
      let value = button.dataset.value || null;
      if (name === 'createLink') {
        value = window.prompt('Đường dẫn liên kết (https://… hoặc mailto:…)')?.trim();
        if (!value) return;
        if (!/^(https?:\/\/|mailto:)/i.test(value) || /[\x00-\x20\x7f]/.test(value)) {
          status.textContent = 'Vui lòng nhập liên kết HTTPS, HTTP hoặc email hợp lệ.'; return;
        }
      }
      command(name, value);
    });
  });
  picker.addEventListener('change', async () => {
    const file = picker.files[0]; if (!file) return;
    if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 3 * 1024 * 1024) {
      status.textContent = 'Chọn ảnh JPG, PNG hoặc WebP tối đa 3 MB.'; return;
    }
    const saved = insertionRange?.cloneRange();
    const button = toolbar.querySelector('[data-command="insertImage"]');
    const data = new FormData(); data.append('photo', file); data.append('csrf', form.elements.csrf.value);
    uploading = true; button.disabled = true; status.textContent = 'Đang tải ảnh…';
    try {
      const response = await fetch(container.dataset.uploadUrl, { method: 'POST', body: data });
      const body = await response.json();
      if (!response.ok || !body.url) throw new Error(body.error || 'Không thể tải ảnh.');
      restore(saved);
      const image = document.createElement('img'); image.src = body.url; image.alt = file.name.replace(/\.[^.]+$/, '');
      document.execCommand('insertHTML', false, image.outerHTML + '<p><br></p>'); remember(); sync();
      status.textContent = 'Đã chèn ảnh. Bạn có thể tiếp tục viết bên dưới.';
    } catch (error) { status.textContent = error.message || 'Không thể tải ảnh. Hãy thử lại.'; }
    finally { uploading = false; button.disabled = false; }
  });
  const insertTable = () => {
    const rows = Number(container.querySelector('[data-table-rows]').value);
    const columns = Number(container.querySelector('[data-table-columns]').value);
    if (!Number.isInteger(rows) || !Number.isInteger(columns) || rows < 1 || rows > 10 || columns < 1 || columns > 8) {
      status.textContent = 'Bảng cần 1–10 hàng và 1–8 cột.'; return;
    }
    restore(insertionRange);
    const table = document.createElement('table');
    const tbody = table.createTBody();
    for (let row = 0; row < rows; row++) {
      const tr = tbody.insertRow();
      for (let col = 0; col < columns; col++) tr.insertCell().innerHTML = '<br>';
    }
    document.execCommand('insertHTML', false, table.outerHTML + '<p><br></p>');
    tablePicker.hidden = true; remember(); sync();
    status.textContent = 'Đã chèn bảng. Đặt con trỏ trong ô để thêm hoặc xóa hàng, cột.';
  };
  container.querySelector('[data-table-insert]').addEventListener('click', insertTable);
  container.querySelector('[data-table-cancel]').addEventListener('click', () => { tablePicker.hidden = true; restore(insertionRange); });
  tablePicker.addEventListener('keydown', event => {
    if (event.key === 'Enter') { event.preventDefault(); insertTable(); }
    if (event.key === 'Escape') { event.preventDefault(); tablePicker.hidden = true; restore(insertionRange); }
  });
  toolbar.querySelectorAll('[data-table-action]').forEach(button => button.addEventListener('click', () => {
    const cell = selectedCell(); if (!cell) return;
    const table = cell.closest('table');
    const tableIndex = [...editor.querySelectorAll('table')].indexOf(table);
    const copy = table.cloneNode(true); const rowIndex = cell.parentElement.rowIndex; const colIndex = cell.cellIndex;
    const action = button.dataset.tableAction;
    let remove = action === 'deleteTable';
    if (action === 'addRow') {
      if (copy.rows.length >= 20) { status.textContent = 'Bảng tối đa 20 hàng.'; return; }
      const row = copy.insertRow(rowIndex + 1);
      for (let i = 0; i < cell.parentElement.cells.length; i++) row.insertCell().innerHTML = '<br>';
    }
    if (action === 'addColumn') {
      if ([...copy.rows].some(row => row.cells.length >= 8)) { status.textContent = 'Bảng tối đa 8 cột.'; return; }
      [...copy.rows].forEach(row => row.insertCell(Math.min(colIndex + 1, row.cells.length)).innerHTML = '<br>');
    }
    if (action === 'deleteRow') { copy.deleteRow(rowIndex); remove = !copy.rows.length; }
    if (action === 'deleteColumn') {
      [...copy.rows].forEach(row => { if (row.cells[colIndex]) row.deleteCell(colIndex); });
      remove = [...copy.rows].every(row => !row.cells.length);
    }
    restore();
    const range = document.createRange(); range.selectNode(table);
    const selected = window.getSelection(); selected.removeAllRanges(); selected.addRange(range);
    document.execCommand('insertHTML', false, remove ? '<p><br></p>' : copy.outerHTML);
    if (!remove) {
      const nextCell = editor.querySelectorAll('table')[tableIndex]?.querySelector('td,th');
      if (nextCell) { const next = document.createRange(); next.selectNodeContents(nextCell); next.collapse(true); selected.removeAllRanges(); selected.addRange(next); }
    }
    remember(); sync();
  }));
  editor.addEventListener('paste', event => {
    event.preventDefault(); document.execCommand('insertText', false, event.clipboardData.getData('text/plain')); remember(); sync();
  });
  form.addEventListener('submit', event => {
    sync();
    if (uploading) { event.preventDefault(); status.textContent = 'Chờ tải ảnh xong trước khi lưu.'; }
    else if (!editor.innerText.replace(/\u00a0/g, ' ').trim() && !editor.querySelector('img')) {
      event.preventDefault(); status.textContent = 'Vui lòng nhập nội dung hoặc chèn ảnh.'; editor.focus();
    } else if (source.value.length > 60000) { event.preventDefault(); status.textContent = 'Nội dung vượt quá độ dài cho phép.'; }
  });
  sync();
});
