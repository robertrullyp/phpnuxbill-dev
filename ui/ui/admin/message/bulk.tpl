{include file="sections/header.tpl"}
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div id="status" class="mb-3"></div>
        <div class="panel panel-primary panel-hovered panel-stacked mb30 {if $page>0 && $totalCustomers >0}hidden{/if}">
            <div class="panel-heading">{Lang::T('Send Bulk Message')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="get" role="form" id="bulkMessageForm" action="">
                    <input type="hidden" name="page" value="{if $page>0 && $totalCustomers==0}-1{else}{$page}{/if}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Router')}</label>
                        <div class="col-md-6">
                            <select class="form-control select2" name="router" id="router">
                                <option value="">{Lang::T('All Routers')}</option>
                                {if $_c['radius_enable']}
                                <option value="radius">{Lang::T('Radius')}</option>
                                {/if}
                                {foreach $routers as $router}
                                <option value="{$router['id']}">{$router['name']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Type')}</label>
                        <div class="col-md-6">
                            <select class="form-control select2" name="service[]" id="service" multiple>
                                <option value="all" selected>{Lang::T('All')}</option>
                                {foreach $service_types as $serviceType}
                                <option value="{$serviceType|escape}">{Lang::T($serviceType)}</option>
                                {/foreach}
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            <small>Pilih <b>All</b> atau satu/lebih jenis layanan berdasarkan type customer.</small>
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Group')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="group" id="group">
                                <option value="all" {if $group=='all' }selected{/if}>{Lang::T('All Customers')}</option>
                                <option value="new" {if $group=='new' }selected{/if}>{Lang::T('New Customers')}</option>
                                <option value="expired" {if $group=='expired' }selected{/if}>{Lang::T('Expired
                                    Customers')}</option>
                                <option value="active" {if $group=='active' }selected{/if}>{Lang::T('Active Customers')}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Message per time')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="batch" id="batch">
                                <option value="5" {if $batch=='5' }selected{/if}>{Lang::T('5 Messages')}</option>
                                <option value="10" {if $batch=='10' }selected{/if}>{Lang::T('10 Messages')}</option>
                                <option value="15" {if $batch=='15' }selected{/if}>{Lang::T('15 Messages')}</option>
                                <option value="20" {if $batch=='20' }selected{/if}>{Lang::T('20 Messages')}</option>
                                <option value="30" {if $batch=='30' }selected{/if}>{Lang::T('30 Messages')}</option>
                                <option value="40" {if $batch=='40' }selected{/if}>{Lang::T('40 Messages')}</option>
                                <option value="50" {if $batch=='50' }selected{/if}>{Lang::T('50 Messages')}</option>
                                <option value="60" {if $batch=='60' }selected{/if}>{Lang::T('60 Messages')}</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            <small>
                                {Lang::T('Use 20 and above if you are sending to many customers')}
                            </small>
                        </p>
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
                                <input type="checkbox" id="wa_queue" name="wa_queue" value="1"> Aktifkan antrian/auto-retry WhatsApp
                            </label>
                        </div>
                        <p class="help-block col-md-4">Hanya berlaku jika WA dipilih.</p>
                    </div>
                    <div class="form-group" id="subject" style="display: none;">
                        <label class="col-md-2 control-label">{Lang::T('Subject')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="subject" id="subjectContent" value=""
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
                            <textarea class="form-control" id="message" name="message" required
                                placeholder="{Lang::T('Compose your message...')}" rows="5">{$message}</textarea>
                            <input name="test" id="test" type="checkbox">
                            <small> {Lang::T('Testing [if checked no real message is sent]')}</small>
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
                            </small>
                        </p>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button type="button" id="startBulk" class="btn btn-primary">{Lang::T('Start Bulk
                                Messaging')}</button>
                            <a href="{Text::url('dashboard')}" class="btn btn-default">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add a Table for Sent History -->
<div class="panel panel-default">
    <div class="panel-heading">{Lang::T('Message Sending History')}</div>
    <div class="panel-body">
        <div id="status"></div>
        <table class="table table-bordered" id="historyTable">
            <thead>
                <tr>
                    <th>{Lang::T('Customer')}</th>
                    <th>{Lang::T('Sent To')}</th>
                    <th>{Lang::T('Channel')}</th>
                    <th>{Lang::T('Status')}</th>
                    <th>{Lang::T('Message')}</th>
                    <th>{Lang::T('Router')}</th>
                    <th>{Lang::T('Service Type')}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailCheckbox = document.getElementById('email');
        const inboxCheckbox = document.getElementById('inbox');
        const subjectDiv = document.getElementById('subject');
        const subjectInput = document.getElementById('subjectContent');

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
        row.setAttribute('draggable', 'true');
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
        row.setAttribute('draggable', 'true');
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

        enableDragSort(byId('wa_builder_buttons'));
        enableDragSort(byId('wa_builder_rows'));
        enableDragSort(byId('wa_builder_template_buttons'));

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
{literal}
<script>
    let page = 0;
    let totalSent = 0;
    let totalFailed = 0;
    let hasMore = true;
    let syncingServiceSelection = false;
    let lastSelectedServices = [];

    // Initialize DataTable
    let historyTable = $('#historyTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true
    });

    function getSelectedServices() {
        const serviceSelect = document.getElementById('service');
        if (!serviceSelect) {
            return [];
        }
        return Array.from(serviceSelect.options)
            .filter(function (option) { return option.selected; })
            .map(function (option) { return option.value; });
    }

    function setSelectedServices(values) {
        const serviceSelect = document.getElementById('service');
        if (!serviceSelect) {
            return;
        }
        const valueMap = {};
        (values || []).forEach(function (value) {
            valueMap[value] = true;
        });
        Array.from(serviceSelect.options).forEach(function (option) {
            option.selected = !!valueMap[option.value];
        });
        if (window.jQuery) {
            window.jQuery(serviceSelect).trigger('change.select2');
        }
        serviceSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function normalizeServiceSelection() {
        if (syncingServiceSelection) {
            return;
        }

        let selectedServices = getSelectedServices();
        let normalizedServices = selectedServices.slice();

        if (selectedServices.length === 0) {
            normalizedServices = ['all'];
        } else if (selectedServices.indexOf('all') !== -1 && selectedServices.length > 1) {
            normalizedServices = lastSelectedServices.indexOf('all') !== -1
                ? selectedServices.filter(function (value) { return value !== 'all'; })
                : ['all'];
        }

        if (normalizedServices.join('|') !== selectedServices.join('|')) {
            syncingServiceSelection = true;
            setSelectedServices(normalizedServices);
            syncingServiceSelection = false;
        }
        lastSelectedServices = normalizedServices.slice();
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.id === 'service') {
            normalizeServiceSelection();
        }
    });
    document.addEventListener('click', function (event) {
        const target = event.target;
        if (!target) {
            return;
        }
        const serviceContainer = document.querySelector('#service + .select2');
        const insideServiceContainer = !!(serviceContainer && serviceContainer.contains(target));
        const serviceResultNode = target.closest('[id^=\"select2-service-result-\"]');
        if (insideServiceContainer || serviceResultNode) {
            setTimeout(function () {
                normalizeServiceSelection();
            }, 0);
        }
    });
    setTimeout(function () {
        normalizeServiceSelection();
    }, 0);

    function sendBatch() {
        if (!hasMore) return;

        $.ajax({
            url: '?_route=message/send_bulk_ajax',
            method: 'POST',
            data: {
                group: $('#group').val(),
                message: $('#message').val(),
                sms: $('#sms').is(':checked') ? '1' : '0',
                wa: $('#wa').is(':checked') ? '1' : '0',
                email: $('#email').is(':checked') ? '1' : '0', 
                inbox: $('#inbox').is(':checked') ? '1' : '0',
                wa_queue: $('#wa_queue').is(':checked') ? '1' : '0',
                batch: $('#batch').val(),
                router: $('#router').val() || '',
                page: page,
                test: $('#test').is(':checked') ? 'on' : 'off',
                service: $('#service').val() || ['all'],
                subject: $('#subjectContent').val(),
            },
            dataType: 'json',
            beforeSend: function () {
                $('#status').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-spinner fa-spin"></i> Sending batch ${page + 1}...
                    </div>
                `);
            },
            success: function (response) {
                if (response && response.status === 'success') {
                    totalSent += response.totalSent || 0;
                    totalFailed += response.totalFailed || 0;
                    page = response.page || 0;
                    hasMore = response.hasMore || false;

                    $('#status').html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Batch ${page} sent! (Total Sent: ${totalSent}, Failed: ${totalFailed})
                        </div>
                    `);

                    (response.batchStatus || []).forEach(msg => {
                        let statusClass = msg.status.includes('Failed') ? 'danger' : 'success';
                        historyTable.row.add([
                            msg.name ? msg.name : 'Unknown Customer',
                            msg.sent ? msg.sent : 'Unknown Recipient',
                            msg.channel ? msg.channel : 'Unknown Channel',
                            `<span class="text-${statusClass}">${msg.status}</span>`,
                            msg.message ? msg.message : 'No Message',
                            msg.router ? msg.router : 'All Router',
                            msg.service ? msg.service : 'No Service'
                        ]).draw(false); // Add row without redrawing the table
                    });

                    if (hasMore) {
                        sendBatch();
                    } else {
                        $('#status').html(`
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> All batches sent! Total Sent: ${totalSent}, Failed: ${totalFailed}
                            </div>
                        `);
                    }
                } else {
                    $('#status').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Error: ${response.message}
                        </div>
                    `);
                }
            },
            error: function () {
                $('#status').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error: Failed to send batch ${page + 1}.
                    </div>
                `);
            }
        });
    }

    // Start sending on button click
    $('#startBulk').on('click', function () {
        page = 0;
        totalSent = 0;
        totalFailed = 0;
        hasMore = true;
        $('#status').html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Starting bulk message sending...</div>');
        historyTable.clear().draw(); // Clear history table before starting
        sendBatch();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
