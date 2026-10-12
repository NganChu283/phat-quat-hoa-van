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
