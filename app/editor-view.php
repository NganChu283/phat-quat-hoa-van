<div class="rich-editor" data-rich-editor>
<label id="editor-label" for="post-body-source"><?= e($editorLabel ?? 'Nội dung bài viết') ?></label>
<div class="editor-toolbar" role="toolbar" aria-label="Định dạng nội dung bài viết" hidden>
<?php foreach ([['bold','B','In đậm',null],['italic','I','In nghiêng',null],['underline','U','Gạch chân',null],['formatBlock','Đoạn','Đoạn văn','p'],['formatBlock','H2','Tiêu đề cấp 2','h2'],['formatBlock','H3','Tiêu đề cấp 3','h3'],['insertUnorderedList','• Danh sách','Danh sách dấu đầu dòng',null],['insertOrderedList','1. Danh sách','Danh sách đánh số',null],['formatBlock','❝','Trích dẫn','blockquote'],['createLink','Liên kết','Chèn liên kết',null],['unlink','Bỏ liên kết','Bỏ liên kết',null],['removeFormat','Xóa định dạng','Xóa định dạng',null],['undo','↶','Hoàn tác',null],['redo','↷','Làm lại',null]] as [$command,$text,$label,$value]): ?>
<button type="button" data-command="<?= e($command) ?>" <?= $value?'data-value="'.e($value).'"':'' ?> title="<?= e($label) ?>" aria-label="<?= e($label) ?>"><?= e($text) ?></button>
<?php endforeach; ?></div>
<div class="editor-content" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="editor-label" hidden><?= post_body($edit ?? ['body'=>'','body_format'=>'plain']) ?></div>
<textarea id="post-body-source" name="body" rows="12" maxlength="60000"><?= e($edit['body'] ?? '') ?></textarea>
<input type="hidden" name="body_format" value="<?= e($edit['body_format'] ?? 'plain') ?>">
<p class="editor-status" data-editor-status role="status">Dùng thanh công cụ để định dạng bài viết.</p>
</div>
