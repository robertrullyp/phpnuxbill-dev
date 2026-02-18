{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-8 col-md-offset-2">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Edit & Resend Message')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" action="{Text::url('message/resend-post')}">
                    <input type="hidden" name="log_id" value="{$log['id']}">
                    <input type="hidden" name="channel" value="{$channel}">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Channel')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" value="{$log['message_type']}" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Recipient')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="recipient" value="{$log['recipient']}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Message')}</label>
                        <div class="col-md-9">
                            {if $channel == 'wa'}
                                <div class="panel panel-info" id="wa_builder">
                                    <div class="panel-heading"><strong>WhatsApp Interactive Builder</strong></div>
                                    <div class="panel-body">
                                        {if $payload_used}
                                            <div class="alert alert-info">Payload interaktif ditemukan, pesan diisi dari payload log.</div>
                                        {/if}
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Mode</label>
                                            <div class="col-md-6">
                                                <select id="wa_builder_mode" class="form-control">
                                                    <option value="buttons">buttons</option>
                                                    <option value="list">list</option>
                                                    <option value="template">template</option>
                                                </select>
                                            </div>
                                            <p class="help-block col-md-4">Mode Pesan Interaktif (Type)</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Text</label>
                                            <div class="col-md-6">
                                                <textarea id="wa_builder_text" class="form-control" rows="3"
                                                    placeholder="Halo [[name]], pilih menu:"></textarea>
                                            </div>
                                            <p class="help-block col-md-4">Isi Teks Pesan</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Header Type</label>
                                            <div class="col-md-6">
                                                <select id="wa_builder_header_type" class="form-control">
                                                    <option value="">{Lang::T('None')}</option>
                                                    <option value="1">Text (1) — isi Header Text</option>
                                                    <option value="2">Image (2) — isi Header Media URL</option>
                                                    <option value="3">Video (3) — isi Header Media URL</option>
                                                    <option value="4">Document (4) — isi Header Media URL</option>
                                                </select>
                                            </div>
                                            <p class="help-block col-md-4">Pilih tipe header sesuai isi yang dipakai.</p>
                                        </div>
                                        <div class="form-group" id="wa_builder_header_text_group" style="display:none;">
                                            <label class="col-md-2 control-label">Header Text</label>
                                            <div class="col-md-6">
                                                <input type="text" id="wa_builder_header_text" class="form-control" placeholder="Header opsional">
                                            </div>
                                        </div>
                                        <div class="form-group" id="wa_builder_header_media_group" style="display:none;">
                                            <label class="col-md-2 control-label">Header Media</label>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <input type="text" id="wa_builder_header_media" class="form-control" placeholder="https://...">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-default" id="wa_builder_pick_media">Upload</button>
                                                    </span>
                                                </div>
                                                <input type="file" id="wa_builder_header_media_file" class="hidden" accept="image/*,video/*,application/pdf">
                                                <div class="progress" id="wa_builder_upload_progress" style="display:none; margin-top:6px;">
                                                    <div class="progress-bar" id="wa_builder_upload_progress_bar" role="progressbar" style="width:0%">0%</div>
                                                </div>
                                                <span class="help-block" id="wa_builder_upload_status"></span>
                                                <div id="wa_builder_upload_preview" class="help-block"></div>
                                            </div>
                                            <p class="help-block col-md-4">Isi URL media atau upload file (otomatis jadi URL sementara).</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Footer</label>
                                            <div class="col-md-6">
                                                <input type="text" id="wa_builder_footer" class="form-control" placeholder="Footer opsional">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Allow Empty Text</label>
                                            <div class="col-md-6">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" id="wa_builder_allow_empty"> true
                                                </label>
                                            </div>
                                        </div>

                                        <div id="wa_builder_buttons_fields">
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Buttons</label>
                                                <div class="col-md-6">
                                                    <div id="wa_builder_buttons"></div>
                                                    <button type="button" class="btn btn-default btn-xs" id="wa_builder_add_button">Add Button</button>
                                                </div>
                                                <p class="help-block col-md-4">Format: id | text</p>
                                            </div>
                                        </div>

                                        <div id="wa_builder_list_fields" style="display:none">
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Title</label>
                                                <div class="col-md-6">
                                                    <input type="text" id="wa_builder_list_title" class="form-control" placeholder="Judul list">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Button Text</label>
                                                <div class="col-md-6">
                                                    <input type="text" id="wa_builder_list_button_text" class="form-control" placeholder="Lihat">
                                                </div>
                                            </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Rows</label>
                                            <div class="col-md-6">
                                                <div id="wa_builder_rows"></div>
                                                <button type="button" class="btn btn-default btn-xs" id="wa_builder_add_row">Add Row</button>
                                            </div>
                                            <p class="help-block col-md-4">Format: section | id | title | description</p>
                                        </div>
                                    </div>

                                        <div id="wa_builder_template_fields" style="display:none">
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Template Buttons</label>
                                                <div class="col-md-6">
                                                    <div id="wa_builder_template_buttons"></div>
                                                    <button type="button" class="btn btn-default btn-xs" id="wa_builder_add_template_button">Add Template Button</button>
                                                </div>
                                                <p class="help-block col-md-4">quick|id|text, url|text|url, call|text|phone</p>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Preview</label>
                                            <div class="col-md-6">
                                                <textarea id="wa_builder_preview" class="form-control" rows="8" readonly></textarea>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-success" id="wa_builder_insert">Insert to Message</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/if}
                            <textarea class="form-control" id="message" name="message" rows="8" required>{$log['message_content']}</textarea>
                        </div>
                    </div>
                    {if $channel == 'other'}
                        <div class="alert alert-warning">
                            {Lang::T('This message type is not supported for resend.')} 
                        </div>
                    {/if}
                    <div class="form-group">
                        <div class="col-md-offset-3 col-md-9">
                            <button type="submit" class="btn btn-success" {if $channel == 'other'}disabled{/if}>
                                {Lang::T('Resend')}
                            </button>
                            <a href="{Text::url('logs/message')}" class="btn btn-default">{Lang::T('Back')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{if $channel == 'wa'}
<script>
(function () {
	function byId(id) {
		return document.getElementById(id);
	}

	function setUploadStatus(text, type) {
		var status = byId('wa_builder_upload_status');
		if (!status) return;
		status.textContent = text || '';
		status.className = 'help-block';
		if (type === 'error') status.className += ' text-danger';
		if (type === 'success') status.className += ' text-success';
	}

	function setUploadProgress(percent) {
		var wrap = byId('wa_builder_upload_progress');
		var bar = byId('wa_builder_upload_progress_bar');
		if (!wrap || !bar) return;
		wrap.style.display = '';
		var value = Math.max(0, Math.min(100, percent || 0));
		bar.style.width = value + '%';
		bar.textContent = value + '%';
	}

	function renderUploadPreview(url, mime) {
		var preview = byId('wa_builder_upload_preview');
		if (!preview) return;
		preview.innerHTML = '';
		if (!url) return;
		var fileName = url.split('/').pop();
		if (mime && mime.indexOf('image/') === 0) {
			var img = document.createElement('img');
			img.src = url;
			img.style.maxWidth = '180px';
			img.style.display = 'block';
			img.style.marginTop = '6px';
			preview.appendChild(img);
		}
		var link = document.createElement('a');
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener';
		link.textContent = fileName || url;
		preview.appendChild(link);
	}

	function uploadHeaderMedia() {
		var input = byId('wa_builder_header_media_file');
		if (!input || !input.files || !input.files[0]) {
			setUploadStatus('Pilih file terlebih dahulu.', 'error');
			return;
		}
		var formData = new FormData();
		formData.append('media', input.files[0]);
		setUploadStatus('Mengunggah...', '');
		setUploadProgress(0);
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '{Text::url('message/wa_media_upload')}', true);
		xhr.upload.onprogress = function (evt) {
			if (evt.lengthComputable) {
				var percent = Math.round((evt.loaded / evt.total) * 100);
				setUploadProgress(percent);
			}
		};
		xhr.onreadystatechange = function () {
			if (xhr.readyState !== 4) return;
			if (xhr.status < 200 || xhr.status >= 300) {
				setUploadStatus('Upload gagal.', 'error');
				return;
			}
			var data = null;
			try { data = JSON.parse(xhr.responseText); } catch (e) {}
			if (!data || !data.ok) {
				setUploadStatus((data && data.message) ? data.message : 'Upload gagal.', 'error');
				return;
			}
			if (byId('wa_builder_header_media')) {
				byId('wa_builder_header_media').value = data.url || '';
			}
			if (byId('wa_builder_header_type') && data.mime) {
				if (data.mime.indexOf('image/') === 0) byId('wa_builder_header_type').value = '2';
				else if (data.mime.indexOf('video/') === 0) byId('wa_builder_header_type').value = '3';
				else byId('wa_builder_header_type').value = '4';
			}
			toggleHeaderFields();
			setUploadProgress(100);
			setUploadStatus('Upload sukses. URL berlaku sampai ' + (data.expires_at || ''), 'success');
			renderUploadPreview(data.url || '', data.mime || '');
			if (typeof updatePreview === 'function') updatePreview();
		};
		xhr.send(formData);
	}

	function addButtonRow(idVal, textVal) {
		var container = byId('wa_builder_buttons');
		if (!container) return;
		var row = document.createElement('div');
		row.className = 'row wa-builder-item';
		row.setAttribute('draggable', 'true');
		row.innerHTML = '<div class="col-xs-5"><input type="text" class="form-control wa-builder-id" placeholder="id"></div>' +
			'<div class="col-xs-5"><input type="text" class="form-control wa-builder-text" placeholder="text"></div>' +
			'<div class="col-xs-2"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
		container.appendChild(row);
		if (typeof idVal !== 'undefined') {
			var idInput = row.querySelector('.wa-builder-id');
			if (idInput) idInput.value = idVal;
		}
		if (typeof textVal !== 'undefined') {
			var textInput = row.querySelector('.wa-builder-text');
			if (textInput) textInput.value = textVal;
		}
	}

	function addRowItem(sectionVal, idVal, titleVal, descVal) {
		var container = byId('wa_builder_rows');
		if (!container) return;
		var row = document.createElement('div');
		row.className = 'row wa-builder-item';
		row.setAttribute('draggable', 'true');
		row.innerHTML = '<div class="col-xs-3"><input type="text" class="form-control wa-builder-row-section" placeholder="section"></div>' +
			'<div class="col-xs-2"><input type="text" class="form-control wa-builder-row-id" placeholder="id"></div>' +
			'<div class="col-xs-3"><input type="text" class="form-control wa-builder-row-title" placeholder="title"></div>' +
			'<div class="col-xs-3"><input type="text" class="form-control wa-builder-row-desc" placeholder="description"></div>' +
			'<div class="col-xs-1"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
		container.appendChild(row);
		if (typeof sectionVal !== 'undefined') {
			var sectionInput = row.querySelector('.wa-builder-row-section');
			if (sectionInput) sectionInput.value = sectionVal;
		}
		if (typeof idVal !== 'undefined') {
			var idInput = row.querySelector('.wa-builder-row-id');
			if (idInput) idInput.value = idVal;
		}
		if (typeof titleVal !== 'undefined') {
			var titleInput = row.querySelector('.wa-builder-row-title');
			if (titleInput) titleInput.value = titleVal;
		}
		if (typeof descVal !== 'undefined') {
			var descInput = row.querySelector('.wa-builder-row-desc');
			if (descInput) descInput.value = descVal;
		}
	}

	function addTemplateButtonRow(typeVal, textVal, valueVal) {
		var container = byId('wa_builder_template_buttons');
		if (!container) return;
		var row = document.createElement('div');
		row.className = 'row wa-builder-item';
		row.setAttribute('draggable', 'true');
		row.innerHTML = '<div class="col-xs-3"><select class="form-control wa-builder-template-type">' +
			'<option value="quick">quick</option>' +
			'<option value="url">url</option>' +
			'<option value="call">call</option>' +
			'</select></div>' +
			'<div class="col-xs-4"><input type="text" class="form-control wa-builder-template-text" placeholder="text"></div>' +
			'<div class="col-xs-4"><input type="text" class="form-control wa-builder-template-value" placeholder="id/url/phone"></div>' +
			'<div class="col-xs-1"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
		container.appendChild(row);
		if (typeof typeVal !== 'undefined') {
			var typeInput = row.querySelector('.wa-builder-template-type');
			if (typeInput) typeInput.value = typeVal;
		}
		if (typeof textVal !== 'undefined') {
			var textInput = row.querySelector('.wa-builder-template-text');
			if (textInput) textInput.value = textVal;
		}
		if (typeof valueVal !== 'undefined') {
			var valueInput = row.querySelector('.wa-builder-template-value');
			if (valueInput) valueInput.value = valueVal;
		}
	}

	var dragItem = null;

	function getDragAfterElement(container, y) {
		var items = Array.prototype.slice.call(container.querySelectorAll('.wa-builder-item:not(.dragging)'));
		var closest = { offset: Number.NEGATIVE_INFINITY, element: null };
		items.forEach(function (child) {
			var box = child.getBoundingClientRect();
			var offset = y - box.top - box.height / 2;
			if (offset < 0 && offset > closest.offset) {
				closest = { offset: offset, element: child };
			}
		});
		return closest.element;
	}

	function enableDragSort(container) {
		if (!container) return;
		container.addEventListener('dragstart', function (e) {
			var item = e.target.closest('.wa-builder-item');
			if (!item) return;
			dragItem = item;
			item.classList.add('dragging');
			if (e.dataTransfer) {
				e.dataTransfer.effectAllowed = 'move';
				try { e.dataTransfer.setData('text/plain', ''); } catch (err) {}
			}
		});
		container.addEventListener('dragover', function (e) {
			if (!dragItem) return;
			e.preventDefault();
			var afterElement = getDragAfterElement(container, e.clientY);
			if (!afterElement) {
				container.appendChild(dragItem);
			} else if (afterElement !== dragItem) {
				container.insertBefore(dragItem, afterElement);
			}
		});
		container.addEventListener('drop', function (e) {
			if (!dragItem) return;
			e.preventDefault();
			var targetItem = e.target.closest('.wa-builder-item');
			if (targetItem && targetItem !== dragItem) {
				var box = targetItem.getBoundingClientRect();
				var insertBefore = e.clientY < (box.top + box.height / 2);
				if (insertBefore) {
					container.insertBefore(dragItem, targetItem);
				} else {
					container.insertBefore(dragItem, targetItem.nextSibling);
				}
			}
			dragItem.classList.remove('dragging');
			dragItem = null;
			updatePreview();
		});
		container.addEventListener('dragend', function () {
			if (dragItem) {
				dragItem.classList.remove('dragging');
				dragItem = null;
				updatePreview();
			}
		});
	}

	function toggleMode() {
		var mode = (byId('wa_builder_mode') || {}).value || 'buttons';
		var buttonsFields = byId('wa_builder_buttons_fields');
		var listFields = byId('wa_builder_list_fields');
		var templateFields = byId('wa_builder_template_fields');
		if (buttonsFields) buttonsFields.style.display = (mode === 'buttons') ? '' : 'none';
		if (listFields) listFields.style.display = (mode === 'list') ? '' : 'none';
		if (templateFields) templateFields.style.display = (mode === 'template') ? '' : 'none';
	}

	function toggleHeaderFields() {
		var headerType = (byId('wa_builder_header_type') || {}).value || '';
		var textGroup = byId('wa_builder_header_text_group');
		var mediaGroup = byId('wa_builder_header_media_group');
		var showText = headerType !== '';
		var showMedia = headerType === '2' || headerType === '3' || headerType === '4';
		if (textGroup) textGroup.style.display = showText ? '' : 'none';
		if (mediaGroup) mediaGroup.style.display = showMedia ? '' : 'none';
	}

	function buildBlock() {
		var mode = (byId('wa_builder_mode') || {}).value || 'buttons';
		var text = (byId('wa_builder_text') || {}).value || '';
		var headerText = (byId('wa_builder_header_text') || {}).value || '';
		var headerType = (byId('wa_builder_header_type') || {}).value || '';
		var headerMedia = (byId('wa_builder_header_media') || {}).value || '';
		var footer = (byId('wa_builder_footer') || {}).value || '';
		var allowEmpty = (byId('wa_builder_allow_empty') || {}).checked;
		var lines = ['[[wa]]'];

		lines.push('[type](' + mode + ')');
		if (text.trim() !== '') {
			text.replace(/\\r\\n/g, '\\n').split('\\n').forEach(function (line) {
				lines.push(line);
			});
		}
		if (headerText.trim() !== '') {
			lines.push('[headerText](' + headerText.trim() + ')');
		}
		if (headerType !== '') {
			lines.push('[headerType](' + headerType + ')');
		}
		if ((headerType === '2' || headerType === '3' || headerType === '4') && headerMedia.trim() !== '') {
			lines.push('[headerMedia](' + headerMedia.trim() + ')');
		}
		if (footer.trim() !== '') {
			lines.push('[footer](' + footer.trim() + ')');
		}
		if (allowEmpty) {
			lines.push('[allowEmptyText](true)');
		}

		if (mode === 'buttons') {
			var btnRows = document.querySelectorAll('#wa_builder_buttons .wa-builder-item');
			btnRows.forEach(function (row) {
				var id = (row.querySelector('.wa-builder-id') || {}).value || '';
				var textVal = (row.querySelector('.wa-builder-text') || {}).value || '';
				if (id.trim() === '' && textVal.trim() === '') return;
				lines.push('[button](' + (id.trim() || textVal.trim()) + '|' + (textVal.trim() || id.trim()) + ')');
			});
		} else if (mode === 'list') {
			var title = (byId('wa_builder_list_title') || {}).value || '';
			var buttonText = (byId('wa_builder_list_button_text') || {}).value || '';
			if (title.trim() !== '') lines.push('[title](' + title.trim() + ')');
			if (buttonText.trim() !== '') lines.push('[buttonText](' + buttonText.trim() + ')');
			var rows = document.querySelectorAll('#wa_builder_rows .wa-builder-item');
			var sectionOrder = [];
			var sectionMap = {};
			rows.forEach(function (row) {
				var sectionVal = (row.querySelector('.wa-builder-row-section') || {}).value || '';
				var id = (row.querySelector('.wa-builder-row-id') || {}).value || '';
				var titleVal = (row.querySelector('.wa-builder-row-title') || {}).value || '';
				var descVal = (row.querySelector('.wa-builder-row-desc') || {}).value || '';
				if (id.trim() === '' && titleVal.trim() === '') return;
				var rowLine = '[row](' + (id.trim() || titleVal.trim()) + '|' + (titleVal.trim() || id.trim());
				if (descVal.trim() !== '') rowLine += '|' + descVal.trim();
				rowLine += ')';
				var sectionName = sectionVal.trim() || 'Menu';
				if (!sectionMap[sectionName]) {
					sectionMap[sectionName] = [];
					sectionOrder.push(sectionName);
				}
				sectionMap[sectionName].push(rowLine);
			});
			sectionOrder.forEach(function (sectionName) {
				lines.push('[section](' + sectionName + ')');
				sectionMap[sectionName].forEach(function (rowLine) {
					lines.push(rowLine);
				});
			});
		} else if (mode === 'template') {
			var templateRows = document.querySelectorAll('#wa_builder_template_buttons .wa-builder-item');
			templateRows.forEach(function (row) {
				var t = (row.querySelector('.wa-builder-template-type') || {}).value || 'quick';
				var textVal = (row.querySelector('.wa-builder-template-text') || {}).value || '';
				var valueVal = (row.querySelector('.wa-builder-template-value') || {}).value || '';
				if (textVal.trim() === '' && valueVal.trim() === '') return;
				if (t === 'quick') {
					lines.push('[button](quick|' + (valueVal.trim() || textVal.trim()) + '|' + (textVal.trim() || valueVal.trim()) + ')');
				} else if (t === 'url') {
					lines.push('[button](url|' + (textVal.trim() || valueVal.trim()) + '|' + valueVal.trim() + ')');
				} else if (t === 'call') {
					lines.push('[button](call|' + (textVal.trim() || valueVal.trim()) + '|' + valueVal.trim() + ')');
				}
			});
		}

		lines.push('[[/wa]]');
		return lines.join('\\n');
	}

	function clearBuilderRows() {
		var btns = byId('wa_builder_buttons');
		if (btns) btns.innerHTML = '';
		var rows = byId('wa_builder_rows');
		if (rows) rows.innerHTML = '';
		var temp = byId('wa_builder_template_buttons');
		if (temp) temp.innerHTML = '';
	}

	function normalizeHeaderType(val) {
		var cleaned = (val || '').toString().trim().toLowerCase();
		if (cleaned === '') return '';
		if (cleaned === '1' || cleaned === 'text') return '1';
		if (cleaned === '2' || cleaned === 'image') return '2';
		if (cleaned === '3' || cleaned === 'video') return '3';
		if (cleaned === '4' || cleaned === 'document' || cleaned === 'doc' || cleaned === 'pdf') return '4';
		return cleaned;
	}

	function detectModeFromInteractive(interactive) {
		if (!interactive || typeof interactive !== 'object') return 'buttons';
		var mode = (interactive.type || '').toString().trim().toLowerCase();
		if (mode === 'buttons' || mode === 'list' || mode === 'template') return mode;
		if (Array.isArray(interactive.sections) && interactive.sections.length > 0) return 'list';
		if (Array.isArray(interactive.buttons) && interactive.buttons.length > 0) {
			var hasTemplate = interactive.buttons.some(function (btn) {
				var t = (btn && btn.type ? btn.type : '').toString().toLowerCase();
				return t === 'quick' || t === 'url' || t === 'call';
			});
			return hasTemplate ? 'template' : 'buttons';
		}
		return 'buttons';
	}

	function applyBuilderData(data) {
		if (!data) return false;
		if (byId('wa_builder_mode') && data.mode) byId('wa_builder_mode').value = data.mode;
		if (byId('wa_builder_text')) byId('wa_builder_text').value = data.text || '';
		if (byId('wa_builder_header_text')) byId('wa_builder_header_text').value = data.headerText || '';
		if (byId('wa_builder_header_type')) byId('wa_builder_header_type').value = data.headerType || '';
		if (byId('wa_builder_header_media')) byId('wa_builder_header_media').value = data.headerMedia || '';
		if (byId('wa_builder_footer')) byId('wa_builder_footer').value = data.footer || '';
		if (byId('wa_builder_allow_empty')) byId('wa_builder_allow_empty').checked = !!data.allowEmptyText;

		clearBuilderRows();

		if (data.mode === 'list') {
			if (byId('wa_builder_list_title')) byId('wa_builder_list_title').value = data.listTitle || '';
			if (byId('wa_builder_list_button_text')) byId('wa_builder_list_button_text').value = data.listButtonText || '';
			if (Array.isArray(data.rows) && data.rows.length) {
				data.rows.forEach(function (row) {
					addRowItem(row.section || '', row.id || '', row.title || '', row.description || '');
				});
			} else {
				addRowItem();
			}
		} else if (data.mode === 'template') {
			if (Array.isArray(data.templateButtons) && data.templateButtons.length) {
				data.templateButtons.forEach(function (btn) {
					addTemplateButtonRow(btn.type || 'quick', btn.text || '', btn.value || '');
				});
			} else {
				addTemplateButtonRow();
			}
		} else {
			if (Array.isArray(data.buttons) && data.buttons.length) {
				data.buttons.forEach(function (btn) {
					addButtonRow(btn.id || '', btn.text || '');
				});
			} else {
				addButtonRow();
			}
		}

		toggleMode();
		toggleHeaderFields();
		updatePreview();
		return true;
	}

	function parseWaBlock(text) {
		if (!text) return null;
		var match = text.match(/\\[\\[wa\\]\\]([\\s\\S]*?)\\[\\[\\/wa\\]\\]/i);
		if (!match) return null;
		var block = match[1] || '';
		var lines = block.split(/\\r?\\n/);
		var data = {
			mode: 'buttons',
			text: '',
			headerType: '',
			headerText: '',
			headerMedia: '',
			footer: '',
			allowEmptyText: false,
			buttons: [],
			templateButtons: [],
			rows: [],
			listTitle: '',
			listButtonText: ''
		};
		var textLines = [];
		var currentSection = 'Menu';

		function applyKeyValue(key, val) {
			if (key === 'type') {
				data.mode = (val || '').toLowerCase().trim() || 'buttons';
				return true;
			}
			if (key === 'text' || key === 'body') {
				if (val !== '') textLines.push(val);
				return true;
			}
			if (key === 'headertext') {
				data.headerText = val;
				if (!data.headerType) data.headerType = '1';
				return true;
			}
			if (key === 'headertype') {
				data.headerType = normalizeHeaderType(val);
				return true;
			}
			if (key === 'headermedia') {
				data.headerMedia = val;
				if (!data.headerType) data.headerType = '2';
				return true;
			}
			if (key === 'footer') {
				data.footer = val;
				return true;
			}
			if (key === 'allowemptytext') {
				data.allowEmptyText = (val || '').toLowerCase() === 'true' || val === '1';
				return true;
			}
			if (key === 'title') {
				data.listTitle = val;
				return true;
			}
			if (key === 'buttontext' || key === 'button_text') {
				data.listButtonText = val;
				return true;
			}
			if (key === 'section') {
				currentSection = val || 'Menu';
				return true;
			}
			if (key === 'row') {
				var parts = val.split('|');
				var row = {
					section: currentSection || 'Menu',
					id: (parts[0] || '').trim(),
					title: (parts[1] || '').trim(),
					description: (parts[2] || '').trim()
				};
				if (!row.title) row.title = row.id;
				if (!row.id) row.id = row.title;
				data.rows.push(row);
				return true;
			}
			if (key === 'button') {
				var partsBtn = val.split('|');
				var first = (partsBtn[0] || '').trim().toLowerCase();
				if (first === 'quick' || first === 'url' || first === 'call') {
					if (data.mode !== 'template') data.mode = 'template';
					var textVal = '';
					var valueVal = '';
					if (first === 'quick') {
						valueVal = (partsBtn[1] || '').trim();
						textVal = (partsBtn[2] || partsBtn[1] || '').trim();
					} else {
						textVal = (partsBtn[1] || '').trim();
						valueVal = (partsBtn[2] || partsBtn[1] || '').trim();
					}
					data.templateButtons.push({
						type: first,
						text: textVal,
						value: valueVal
					});
				} else {
					data.buttons.push({
						id: (partsBtn[0] || '').trim(),
						text: (partsBtn[1] || partsBtn[0] || '').trim()
					});
				}
				return true;
			}
			return false;
		}

		for (var i = 0; i < lines.length; i++) {
			var rawLine = lines[i];
			var line = rawLine.trim();
			if (!line) {
				textLines.push('');
				continue;
			}

			var lower = line.toLowerCase();
			if (lower.indexOf('[text](') === 0 || lower.indexOf('[body](') === 0) {
				var textKey = lower.indexOf('[body](') === 0 ? 'body' : 'text';
				var prefix = '[' + textKey + '](';
				var startIndex = rawLine.toLowerCase().indexOf(prefix);
				var firstPart = rawLine.substring(startIndex + prefix.length);
				var buffer = [];
				if (line.slice(-1) === ')') {
					buffer.push(firstPart.replace(/\\)\\s*$/, ''));
				} else {
					buffer.push(firstPart);
					for (i = i + 1; i < lines.length; i++) {
						var nextRaw = lines[i];
						var nextLine = nextRaw.trim();
						if (nextLine === '') {
							buffer.push('');
							continue;
						}
						if (/\\)\\s*$/.test(nextLine)) {
							buffer.push(nextRaw.replace(/\\)\\s*$/, ''));
							break;
						}
						buffer.push(nextRaw);
					}
				}
				buffer.forEach(function (txtLine) {
					textLines.push(txtLine);
				});
				continue;
			}

			if (line.charAt(0) === '[') {
				var sepIndex = line.indexOf('](');
				if (sepIndex === -1 || line.slice(-1) !== ')') {
					textLines.push(rawLine);
					continue;
				}
				var key = line.substring(1, sepIndex).toLowerCase();
				var val = line.substring(sepIndex + 2, line.length - 1);
				if (applyKeyValue(key, val)) continue;
				textLines.push(rawLine);
				continue;
			}

			var match = line.match(/^([A-Za-z0-9_]+)\\s*[:=]\\s*(.*)$/);
			if (match) {
				if (applyKeyValue(match[1].toLowerCase(), match[2])) continue;
			}
			textLines.push(rawLine);
		}
		data.text = textLines.join('\\n').trim();
		if (data.headerText && !data.headerType) data.headerType = '1';
		if (data.headerMedia && !data.headerType) data.headerType = '2';
		data.headerType = normalizeHeaderType(data.headerType);
		return data;
	}

	function parsePayloadJson(raw) {
		if (!raw) return null;
		var trimmed = raw.trim();
		var firstChar = trimmed.charAt(0);
		if (!trimmed || (firstChar !== String.fromCharCode(123) && firstChar !== String.fromCharCode(91))) return null;
		var payload = null;
		try { payload = JSON.parse(trimmed); } catch (e) { return null; }
		if (!payload || typeof payload !== 'object' || Array.isArray(payload)) return null;

		var interactive = payload.interactive || null;
		var data = {
			mode: 'buttons',
			text: '',
			headerType: '',
			headerText: '',
			headerMedia: '',
			footer: '',
			allowEmptyText: false,
			buttons: [],
			templateButtons: [],
			rows: [],
			listTitle: '',
			listButtonText: ''
		};

		if (interactive && typeof interactive === 'object') {
			data.mode = detectModeFromInteractive(interactive);
			data.text = (interactive.text || interactive.body || '').toString();
			data.footer = (interactive.footer || '').toString();
			data.headerText = (interactive.headerText || '').toString();
			data.headerType = normalizeHeaderType(interactive.headerType);
			if (interactive.headerMedia && typeof interactive.headerMedia === 'object') {
				data.headerMedia = interactive.headerMedia.url || interactive.headerMedia.path || '';
				if (!data.headerType && interactive.headerMedia.type) {
					data.headerType = normalizeHeaderType(interactive.headerMedia.type);
				}
			}
			if (!data.headerType && data.headerText) data.headerType = '1';
			if (!data.headerType && data.headerMedia) data.headerType = '2';

			if (data.mode === 'list') {
				data.listTitle = (interactive.title || '').toString();
				data.listButtonText = (interactive.buttonText || '').toString();
				if (Array.isArray(interactive.sections)) {
					interactive.sections.forEach(function (section) {
						var sectionTitle = (section && section.title ? section.title : 'Menu').toString();
						if (Array.isArray(section.rows)) {
							section.rows.forEach(function (row) {
								if (!row || typeof row !== 'object') return;
								var idVal = (row.id || '').toString();
								var titleVal = (row.title || '').toString();
								var descVal = (row.description || '').toString();
								data.rows.push({
									section: sectionTitle,
									id: idVal,
									title: titleVal || idVal,
									description: descVal
								});
							});
						}
					});
				}
			} else if (data.mode === 'template') {
				if (Array.isArray(interactive.buttons)) {
					interactive.buttons.forEach(function (btn) {
						if (!btn || typeof btn !== 'object') return;
						var t = (btn.type || 'quick').toString().toLowerCase();
						var textVal = (btn.text || btn.title || '').toString();
						var valueVal = '';
						if (t === 'url') valueVal = (btn.url || '').toString();
						else if (t === 'call') valueVal = (btn.phoneNumber || '').toString();
						else valueVal = (btn.id || btn.text || btn.title || '').toString();
						data.templateButtons.push({
							type: t,
							text: textVal || valueVal,
							value: valueVal || textVal
						});
					});
				}
			} else {
				if (Array.isArray(interactive.buttons)) {
					interactive.buttons.forEach(function (btn) {
						if (!btn || typeof btn !== 'object') return;
						var idVal = (btn.id || btn.text || btn.title || '').toString();
						var textVal = (btn.text || btn.title || btn.id || '').toString();
						data.buttons.push({ id: idVal, text: textVal });
					});
				}
			}
		} else {
			data.text = (payload.text || payload.body || '').toString();
		}

		data.allowEmptyText = !!(payload.allowEmptyText || payload.allowEmpty || (interactive && interactive.allowEmptyText));
		return data;
	}

	function autofillBuilderFromMessage() {
		var message = byId('message');
		if (!message) return;
		var raw = message.value || '';
		var data = parsePayloadJson(raw) || parseWaBlock(raw);
		if (!data) return;
		applyBuilderData(data);
	}

	function updatePreview() {
		var preview = byId('wa_builder_preview');
		if (!preview) return;
		preview.value = buildBlock();
	}

	function insertToMessage() {
		var preview = byId('wa_builder_preview');
		var target = byId('message');
		if (!preview || !target) return;
		var block = preview.value;
		var start = target.selectionStart;
		var end = target.selectionEnd;
		if (typeof start === 'number' && typeof end === 'number') {
			var before = target.value.substring(0, start);
			var after = target.value.substring(end);
			target.value = before + block + after;
			target.selectionStart = target.selectionEnd = start + block.length;
		} else {
			target.value = (target.value ? target.value + "\n" : "") + block;
		}
		target.focus();
	}

	function initBuilder() {
	if (!byId('wa_builder')) return;
	if (byId('wa_builder_add_button')) byId('wa_builder_add_button').addEventListener('click', function () {
		addButtonRow();
		updatePreview();
	});
		if (byId('wa_builder_add_row')) byId('wa_builder_add_row').addEventListener('click', function () {
			addRowItem();
			updatePreview();
		});
	if (byId('wa_builder_add_template_button')) byId('wa_builder_add_template_button').addEventListener('click', function () {
		addTemplateButtonRow();
		updatePreview();
	});
	if (byId('wa_builder_pick_media')) byId('wa_builder_pick_media').addEventListener('click', function () {
		var input = byId('wa_builder_header_media_file');
		if (input) input.click();
	});
	if (byId('wa_builder_header_media_file')) byId('wa_builder_header_media_file').addEventListener('change', function () {
		uploadHeaderMedia();
	});
	if (byId('wa_builder_insert')) byId('wa_builder_insert').addEventListener('click', insertToMessage);
		if (byId('wa_builder_mode')) byId('wa_builder_mode').addEventListener('change', function () {
			toggleMode();
			updatePreview();
		});
		if (byId('wa_builder_header_type')) byId('wa_builder_header_type').addEventListener('change', function () {
			toggleHeaderFields();
			updatePreview();
		});

		document.addEventListener('click', function (e) {
			if (e.target.classList.contains('wa-builder-remove')) {
				var item = e.target.closest('.wa-builder-item');
				if (item) item.remove();
				updatePreview();
			}
		});
		document.addEventListener('input', function (e) {
			if (e.target.closest('#wa_builder')) {
				updatePreview();
			}
		});
		document.addEventListener('change', function (e) {
			if (e.target.closest('#wa_builder')) {
				updatePreview();
			}
		});

		enableDragSort(byId('wa_builder_buttons'));
		enableDragSort(byId('wa_builder_rows'));
		enableDragSort(byId('wa_builder_template_buttons'));

		addButtonRow();
		addRowItem();
		addTemplateButtonRow();
		toggleMode();
		toggleHeaderFields();
		updatePreview();
		autofillBuilderFromMessage();
	}

	document.addEventListener('DOMContentLoaded', initBuilder);
})();
</script>
{/if}

{include file="sections/footer.tpl"}
