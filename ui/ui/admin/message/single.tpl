{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12 col-md-12">
		<div class="panel panel-primary panel-hovered panel-stacked mb30">
			<div class="panel-heading">{Lang::T('Send Personal Message')}</div>
			<div class="panel-body">
				<form class="form-horizontal" method="post" role="form" action="{Text::url('message/send-post')}">
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Customer')}</label>
						<div class="col-md-6">
							<select {if $cust}{else}id="personSelect" {/if} class="form-control select2"
								name="id_customer" style="width: 100%"
								data-placeholder="{Lang::T('Select a customer')}...">
								{if $cust}
								<option value="{$cust['id']}">{$cust['username']} &bull; {$cust['fullname']} &bull;
									{$cust['email']}</option>
								{/if}
							</select>
						</div>
					</div>
					<div class="form-group" id="via">
						<label class="col-md-2 control-label">{Lang::T('Channel')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="sms" name="sms" value="1">
							{Lang::T('SMS')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="wa" name="wa" value="1">
							{Lang::T('WA')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="email" name="email" value="1">
							{Lang::T('Email')}</label>
						<label class="col-md-1 control-label"><input type="checkbox" id="inbox" name="inbox" value="1">
							{Lang::T('Inbox')}</label>
					</div>
					<div class="form-group" id="wa_queue_group">
						<label class="col-md-2 control-label">WA Queue</label>
						<div class="col-md-6">
							<label class="checkbox-inline">
								<input type="checkbox" name="wa_queue" value="1"> Aktifkan antrian/auto-retry WhatsApp
							</label>
						</div>
						<p class="help-block col-md-4">Gunakan queue agar pesan WA retry otomatis saat gagal.</p>
					</div>
					<div class="form-group" id="subject" style="display: none;">
						<label class="col-md-2 control-label">{Lang::T('Subject')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="subject" id="subject-content" value=""
								placeholder="{Lang::T('Enter message subject here')}">
						</div>
						<p class="help-block col-md-4">
							<small>
								{Lang::T('You can also use the below placeholders here too')}.
							</small>
						</p>
					</div>
					<div class="form-group">
						<div class="col-md-12">
							<div class="panel panel-info" id="wa_builder">
								<div class="panel-heading"><strong>WhatsApp Interactive Builder</strong></div>
								<div class="panel-body">
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
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Message')}</label>
						<div class="col-md-6">
							<textarea class="form-control" id="message" name="message"
								placeholder="{Lang::T('Compose your message...')}" rows="5"></textarea>
						</div>
						<p class="help-block col-md-4">
							<small>
								{Lang::T('Use placeholders:')}
								<br>
								<b>[[name]]</b> - {Lang::T('Customer Name')}
								<br>
								<b>[[user_name]]</b> - {Lang::T('Customer Username')}
								<br>
								<b>[[phone]]</b> - {Lang::T('Customer Phone')}
								<br>
								<b>[[company_name]]</b> - {Lang::T('Your Company Name')}
								<br>
								<b>[[payment_link]]</b> - <a
									href="{Text::url('docs')}/#Reminder%20with%20payment%20link"
									target="_blank">{Lang::T('Read documentation')}</a>.
							</small>
						</p>
					</div>

					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-success"
								onclick="return ask(this, '{Lang::T('Continue the process of sending messages')}?')"
								type="submit">{Lang::T('Send Message')}</button>
							<a href="{Text::url('dashboard')}" class="btn btn-default">{Lang::T('Cancel')}</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const emailCheckbox = document.getElementById('email');
		const inboxCheckbox = document.getElementById('inbox');
		const subjectDiv = document.getElementById('subject');
		const subjectInput = document.getElementById('subject-content');

		function toggleSubjectField() {
			if (emailCheckbox.checked || inboxCheckbox.checked) {
				subjectDiv.style.display = 'block';
				subjectInput.required = true;
			} else {
				subjectDiv.style.display = 'none';
				subjectInput.required = false;
				subjectInput.value = '';
			}
		}

		emailCheckbox.addEventListener('change', toggleSubjectField);
		inboxCheckbox.addEventListener('change', toggleSubjectField);
	});
</script>

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

	function addButtonRow() {
		var container = byId('wa_builder_buttons');
		if (!container) return;
		var row = document.createElement('div');
		row.className = 'row wa-builder-item';
		row.innerHTML = '<div class="col-xs-5"><input type="text" class="form-control wa-builder-id" placeholder="id"></div>' +
			'<div class="col-xs-5"><input type="text" class="form-control wa-builder-text" placeholder="text"></div>' +
			'<div class="col-xs-2"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
		container.appendChild(row);
	}

	function addRowItem() {
		var container = byId('wa_builder_rows');
		if (!container) return;
		var row = document.createElement('div');
		row.className = 'row wa-builder-item';
		row.innerHTML = '<div class="col-xs-3"><input type="text" class="form-control wa-builder-row-section" placeholder="section"></div>' +
			'<div class="col-xs-2"><input type="text" class="form-control wa-builder-row-id" placeholder="id"></div>' +
			'<div class="col-xs-3"><input type="text" class="form-control wa-builder-row-title" placeholder="title"></div>' +
			'<div class="col-xs-3"><input type="text" class="form-control wa-builder-row-desc" placeholder="description"></div>' +
			'<div class="col-xs-1"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
		container.appendChild(row);
	}

	function addTemplateButtonRow() {
		var container = byId('wa_builder_template_buttons');
		if (!container) return;
		var row = document.createElement('div');
		row.className = 'row wa-builder-item';
		row.innerHTML = '<div class="col-xs-3"><select class="form-control wa-builder-template-type">' +
			'<option value="quick">quick</option>' +
			'<option value="url">url</option>' +
			'<option value="call">call</option>' +
			'</select></div>' +
			'<div class="col-xs-4"><input type="text" class="form-control wa-builder-template-text" placeholder="text"></div>' +
			'<div class="col-xs-4"><input type="text" class="form-control wa-builder-template-value" placeholder="id/url/phone"></div>' +
			'<div class="col-xs-1"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
		container.appendChild(row);
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
			text.replace(/\r\n/g, '\n').split('\n').forEach(function (line) {
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
		return lines.join('\n');
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

		addButtonRow();
		addRowItem();
		addTemplateButtonRow();
		toggleMode();
		toggleHeaderFields();
		updatePreview();
	}

	document.addEventListener('DOMContentLoaded', initBuilder);
})();
</script>

{include file="sections/footer.tpl"}
