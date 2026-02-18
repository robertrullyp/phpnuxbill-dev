{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{Text::url('settings/notifications-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('User Notification')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">WA Queue</label>
                        <div class="col-md-6">
                            <input type="hidden" name="wa_queue_enabled" value="0">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="wa_queue_enabled" value="1"
                                    {if isset($_json['wa_queue_enabled']) && ($_json['wa_queue_enabled']=='1' || $_json['wa_queue_enabled']=='yes' || $_json['wa_queue_enabled']=='true')}checked{/if}>
                                Aktifkan antrian/auto-retry untuk notifikasi WhatsApp
                            </label>
                        </div>
                        <p class="help-block col-md-4">Berlaku untuk semua template notifikasi WA.</p>
                    </div>
                    <div class="panel panel-info" id="wa_builder">
                        <div class="panel-heading"><strong>WhatsApp Interactive Builder</strong></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-md-2 control-label">Target</label>
                                <div class="col-md-6">
                                    <select id="wa_builder_target" class="form-control">
                                        <option value="otp_message">{Lang::T('OTP Message')}</option>
                                        <option value="expired">{Lang::T('Expired Notification Message')}</option>
                                        <option value="reminder_7_day">{Lang::T('Reminder 7 days')}</option>
                                        <option value="reminder_3_day">{Lang::T('Reminder 3 days')}</option>
                                        <option value="reminder_1_day">{Lang::T('Reminder 1 day')}</option>
                                        <option value="invoice_paid">{Lang::T('Invoice Notification Payment')}</option>
                                        <option value="invoice_balance">{Lang::T('Balance Notification Payment')}</option>
                                        <option value="welcome_message">{Lang::T('Welcome Message')}</option>
                                        <option value="plan_change_message">{Lang::T('Plan Change Notification')}</option>
                                        <option value="edit_expiry_message">{Lang::T('Expiry Edit Notification')}</option>
                                        {if $_c['enable_balance'] == 'yes'}
                                        <option value="balance_send">{Lang::T('Send Balance')}</option>
                                        <option value="balance_received">{Lang::T('Received Balance')}</option>
                                        {/if}
                                    </select>
	                                </div>
	                                <p class="help-block col-md-4">Pilih template yang ingin diisi.</p>
	                            </div>
		                            <div class="form-group" id="wa_builder_override_enabled_row">
		                                <label class="col-md-2 control-label">Override Template</label>
		                                <div class="col-md-6">
		                                    <label class="checkbox-inline">
		                                        <input type="checkbox" id="wa_builder_override_enabled"> {Lang::T('Override template')} <span id="wa_builder_override_enabled_hint" class="text-muted"></span>
		                                    </label>
	                                </div>
	                                <p class="help-block col-md-4">Jika aktif, tombol Insert berubah jadi Save dan akan menyimpan override tanpa mengubah template global.</p>
	                            </div>
	                            <div class="form-group" id="wa_builder_override_scope_row" style="display:none;">
	                                <label class="col-md-2 control-label">Mode Override</label>
	                                <div class="col-md-6">
	                                    <select id="wa_builder_override_scope" class="form-control">
	                                        <option value="type">Per Kategori</option>
	                                        <option value="plan">Per Plan</option>
	                                    </select>
	                                </div>
	                            </div>
	                            <div class="form-group" id="wa_builder_override_type_row" style="display:none;">
	                                <label class="col-md-2 control-label">Kategori</label>
	                                <div class="col-md-6">
	                                    <select id="wa_builder_override_type" class="form-control">
	                                        <option value="HOTSPOT">Hotspot</option>
	                                        <option value="PPPOE">PPPoE</option>
	                                        <option value="VPN">VPN</option>
	                                    </select>
	                                </div>
	                            </div>
		                            <div class="form-group" id="wa_builder_override_plan_row" style="display:none;">
		                                <label class="col-md-2 control-label">Plan</label>
		                                <div class="col-md-6">
		                                    <select id="wa_builder_override_plan" class="form-control">
		                                        <option value="">- pilih plan -</option>
		                                        {foreach $plans as $p}
		                                            <option value="{$p['id']}" data-type="{$p['type']|escape}">#{$p['id']} - {$p['type']} - {Lang::htmlspecialchars($p['name_plan'])}</option>
		                                        {/foreach}
		                                    </select>
		                                </div>
		                            </div>
	                            <div class="form-group" id="wa_builder_override_purpose_row" style="display:none;">
	                                <label class="col-md-2 control-label">{Lang::T('Purpose')}</label>
	                                <div class="col-md-6">
	                                    <select id="wa_builder_override_purpose" class="form-control"></select>
	                                </div>
	                                <p class="help-block col-md-4">{Lang::T('Select purpose for this override.')}</p>
	                            </div>
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
                                <label class="col-md-2 control-label">Header Text</label>
                                <div class="col-md-6">
                                    <input type="text" id="wa_builder_header_text" class="form-control" placeholder="Header opsional">
                                </div>
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
	                            <div class="form-group" style="display:none;">
	                                <label class="col-md-2 control-label">Header Media URL</label>
	                                <div class="col-md-6">
	                                    <input type="text" id="wa_builder_header_media" class="form-control" placeholder="https://...">
	                                </div>
	                                <p class="help-block col-md-4">Contoh: https://... (Image/Video/Document)</p>
	                            </div>
	                            <div class="form-group" style="display:none;">
	                                <label class="col-md-2 control-label">Header Media Upload</label>
	                                <div class="col-md-6">
	                                    <input type="file" id="wa_builder_header_media_file" class="form-control" accept="image/*,video/*,application/pdf">
	                                    <button type="button" class="btn btn-default btn-xs" id="wa_builder_upload_media">Upload</button>
	                                    <div class="progress" id="wa_builder_upload_progress" style="display:none; margin-top:6px;">
                                        <div class="progress-bar" id="wa_builder_upload_progress_bar" role="progressbar" style="width:0%">0%</div>
                                    </div>
                                    <span class="help-block" id="wa_builder_upload_status"></span>
                                    <div id="wa_builder_upload_preview" class="help-block"></div>
                                </div>
                                <p class="help-block col-md-4">Upload jadi URL sementara (hapus otomatis max 7 hari).</p>
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
	                                    <p class="help-block col-md-4">quick|id|text, url|text|url, call|text|phone, copy|text|code</p>
	                                </div>
	                            </div>

		                            <div class="form-group">
		                                <label class="col-md-2 control-label">Preview</label>
		                                <div class="col-md-6">
		                                    <textarea id="wa_builder_preview" class="form-control" rows="8" readonly></textarea>
		                                </div>
		                                <div class="col-md-4">
		                                    <div style="display:flex;flex-direction:column;align-items:flex-start;gap:6px;">
		                                        <button type="button" class="btn btn-success" id="wa_builder_insert">Insert to Target</button>
		                                        <button type="button" class="btn btn-default" id="wa_builder_test_send">{Lang::T('Test Send')}</button>
		                                        <p class="help-block" id="wa_builder_test_status" style="margin:0;"></p>
		                                    </div>
		                                </div>
			                            </div>
			                        </div>
			                    </div>
					                    <div class="panel panel-default" id="wa_template_preview_panel" style="margin-top:10px;">
					                        <div class="panel-heading"><strong>{Lang::T('Override Templates')}</strong></div>
					                        <div class="panel-body">
					                            <div class="form-group">
					                                <label class="col-md-2 control-label">{Lang::T('Override Mode')}</label>
					                                <div class="col-md-6">
					                                    <select id="wa_template_preview_mode" class="form-control">
					                                        <option value="type">{Lang::T('Per Category')}</option>
					                                        <option value="plan">{Lang::T('Per Plan')}</option>
					                                        <option value="purpose">{Lang::T('Per Purpose')}</option>
					                                    </select>
					                                </div>
			                                <div class="col-md-4">
			                                    <button type="button" class="btn btn-danger btn-sm" id="wa_template_preview_delete" style="display:none;">{Lang::T('Delete Override')}</button>
			                                    <button type="button" class="btn btn-primary btn-sm" id="wa_template_preview_edit" style="display:none;margin-top:6px;">{Lang::T('Edit in Builder')}</button>
			                                </div>
				                            </div>
				                            <div class="form-group" id="wa_template_preview_target_row" style="display:none;">
				                                <label class="col-md-2 control-label">{Lang::T('Target')}</label>
				                                <div class="col-md-6">
				                                    <select id="wa_template_preview_target" class="form-control"></select>
				                                </div>
				                            </div>
				                            <div class="form-group" id="wa_template_preview_type_row" style="display:none;">
				                                <label class="col-md-2 control-label">{Lang::T('Category')}</label>
				                                <div class="col-md-6">
				                                    <select id="wa_template_preview_type" class="form-control"></select>
				                                </div>
				                            </div>
				                            <div class="form-group" id="wa_template_preview_plan_row" style="display:none;">
				                                <label class="col-md-2 control-label">{Lang::T('Plan')}</label>
				                                <div class="col-md-6">
				                                    <select id="wa_template_preview_plan" class="form-control"></select>
				                                </div>
				                            </div>
				                            <div class="form-group" id="wa_template_preview_purpose_row" style="display:none;">
				                                <label class="col-md-2 control-label">{Lang::T('Purpose')}</label>
				                                <div class="col-md-6">
				                                    <select id="wa_template_preview_purpose" class="form-control"></select>
				                                </div>
				                            </div>
				                            <div class="form-group">
				                                <label class="col-md-2 control-label">{Lang::T('Override Content')}</label>
				                                <div class="col-md-6">
				                                    <textarea id="wa_template_preview_text" class="form-control" rows="8" readonly></textarea>
				                                    <span class="help-block" id="wa_template_preview_hint"></span>
				                                </div>
				                                <p class="help-block col-md-4" id="wa_template_preview_priority_hint">
				                                    <small>
				                                        <b>{Lang::T('Priority rules (used by system)')}:</b><br>
				                                        {Lang::T('Plan-related templates')}: {Lang::T('Per Plan override')} → {Lang::T('Per Category override')} → {Lang::T('Global template')}<br>
				                                        {Lang::T('Purpose-based templates (OTP/Welcome)')}: {Lang::T('Per Purpose override')} → {Lang::T('Global template')}<br>
				                                        {Lang::T('Other templates')}: {Lang::T('Global template')}<br>
				                                        <span class="text-muted">{Lang::T('Overrides apply only when the selected template supports that override mode.')}</span>
				                                    </small>
				                                </p>
				                            </div>
		                        </div>
		                    </div>
		                    <div class="form-group">
		                        <label class="col-md-2 control-label">OTP Message</label>
		                        <div class="col-md-6">
		                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="otp_message" data-target="otp_message">Test Send</button>
	                            <span class="help-block" id="wa_test_status_otp_message"></span>
	                            <textarea class="form-control" id="otp_message" name="otp_message" rows="4"
	                                placeholder="[[company]]&#10;&#10;Kode OTP: [[otp]]">{if isset($_json['otp_message']) && $_json['otp_message']!=''}{Lang::htmlspecialchars($_json['otp_message'])}{else}{Lang::htmlspecialchars($_default['otp_message'])}{/if}</textarea>
	                        </div>
		                        <p class="help-block col-md-4">
		                            <b>[[company]]</b> - otomatis diganti nama perusahaan.<br>
		                            <b>[[otp]]</b> - otomatis diganti kode OTP.<br>
		                            <b>[[purpose]]</b> - opsional, tujuan OTP (Register/Verify/Forgot).<br>
		                            <b>[[otp_expires_at]]</b> - {Lang::T('OTP expiration time')}.<br>
		                            <b>[[otp_request_allowed_at]]</b> - {Lang::T('Next OTP request allowed time')}.<br>
		                            <b>[[otp_expiry_seconds]]</b> - {Lang::T('OTP validity (seconds)')}.<br>
		                            <b>[[otp_wait_seconds]]</b> - {Lang::T('OTP resend wait time (seconds)')}.
		                        </p>
		                    </div>
	                    <div class="form-group">
	                        <label class="col-md-2 control-label">{Lang::T('Expired Notification Message')}</label>
	                        <div class="col-md-6">
	                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_expired" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_expired" value="1"
                                        {if isset($_json['wa_queue_expired']) && ($_json['wa_queue_expired']=='1' || $_json['wa_queue_expired']=='yes' || $_json['wa_queue_expired']=='true' || $_json['wa_queue_expired']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="expired" data-target="expired">Test Send</button>
                            <span class="help-block" id="wa_test_status_expired"></span>
                            <textarea class="form-control" id="expired" name="expired"
                                placeholder="{Lang::T('Hello')} [[name]], {Lang::T('your internet package')} [[package]] {Lang::T('has been expired')}"
                                rows="4">{if $_json['expired']!=''}{Lang::htmlspecialchars($_json['expired'])}{else}{Lang::T('Hello')} [[name]], {Lang::T('your internet package')} [[package]] {Lang::T('has been expired')}.{/if}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[package]]</b> - {Lang::T('will be replaced with Package name')}.<br>
                            <b>[[price]]</b> - {Lang::T('will be replaced with Package price')}.<br>
                            <b>[[bills]]</b> - {Lang::T('additional bills for customers')}.<br>
                            <b>[[payment_link]]</b> - <a href="{$app_url}/docs/#Reminder%20with%20payment%20link"
                                target="_blank">{Lang::T("read documentation")}</a>.
                            <br><b>[[extend_link]]</b> - {Lang::T('will be replaced with Customer self-extend link')}.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder 7 days')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_reminder_7_day" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_reminder_7_day" value="1"
                                        {if isset($_json['wa_queue_reminder_7_day']) && ($_json['wa_queue_reminder_7_day']=='1' || $_json['wa_queue_reminder_7_day']=='yes' || $_json['wa_queue_reminder_7_day']=='true' || $_json['wa_queue_reminder_7_day']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="reminder_7_day" data-target="reminder_7_day">Test Send</button>
                            <span class="help-block" id="wa_test_status_reminder_7_day"></span>
                            <textarea class="form-control" id="reminder_7_day" name="reminder_7_day"
                                rows="4">{Lang::htmlspecialchars($_json['reminder_7_day'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[package]]</b> - {Lang::T('will be replaced with Package name')}.<br>
                            <b>[[price]]</b> - {Lang::T('will be replaced with Package price')}.<br>
                            <b>[[expired_date]]</b> - {Lang::T('will be replaced with Expiration date')}.<br>
                            <b>[[bills]]</b> - {Lang::T('additional bills for customers')}.<br>
                            <b>[[payment_link]]</b> - <a href="{$app_url}/docs/#Reminder%20with%20payment%20link"
                                target="_blank">{Lang::T("read documentation")}</a>.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder 3 days')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_reminder_3_day" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_reminder_3_day" value="1"
                                        {if isset($_json['wa_queue_reminder_3_day']) && ($_json['wa_queue_reminder_3_day']=='1' || $_json['wa_queue_reminder_3_day']=='yes' || $_json['wa_queue_reminder_3_day']=='true' || $_json['wa_queue_reminder_3_day']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="reminder_3_day" data-target="reminder_3_day">Test Send</button>
                            <span class="help-block" id="wa_test_status_reminder_3_day"></span>
                            <textarea class="form-control" id="reminder_3_day" name="reminder_3_day"
                                rows="4">{Lang::htmlspecialchars($_json['reminder_3_day'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[package]]</b> - {Lang::T('will be replaced with Package name')}.<br>
                            <b>[[price]]</b> - {Lang::T('will be replaced with Package price')}.<br>
                            <b>[[expired_date]]</b> - {Lang::T('will be replaced with Expiration date')}.<br>
                            <b>[[bills]]</b> - {Lang::T('additional bills for customers')}.<br>
                            <b>[[payment_link]]</b> - <a href="{$app_url}/docs/#Reminder%20with%20payment%20link"
                                target="_blank">{Lang::T("read documentation")}</a>.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder 1 day')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_reminder_1_day" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_reminder_1_day" value="1"
                                        {if isset($_json['wa_queue_reminder_1_day']) && ($_json['wa_queue_reminder_1_day']=='1' || $_json['wa_queue_reminder_1_day']=='yes' || $_json['wa_queue_reminder_1_day']=='true' || $_json['wa_queue_reminder_1_day']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="reminder_1_day" data-target="reminder_1_day">Test Send</button>
                            <span class="help-block" id="wa_test_status_reminder_1_day"></span>
                            <textarea class="form-control" id="reminder_1_day" name="reminder_1_day"
                                rows="4">{Lang::htmlspecialchars($_json['reminder_1_day'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[package]]</b> - {Lang::T('will be replaced with Package name')}.<br>
                            <b>[[price]]</b> - {Lang::T('will be replaced with Package price')}.<br>
                            <b>[[expired_date]]</b> - {Lang::T('will be replaced with Expiration date')}.<br>
                            <b>[[bills]]</b> - {Lang::T('additional bills for customers')}.<br>
                            <b>[[payment_link]]</b> - <a href="{$app_url}/docs/#Reminder%20with%20payment%20link"
                                target="_blank">{Lang::T("read documentation")}</a>.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Invoice Notification Payment')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_invoice_paid" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_invoice_paid" value="1"
                                        {if isset($_json['wa_queue_invoice_paid']) && ($_json['wa_queue_invoice_paid']=='1' || $_json['wa_queue_invoice_paid']=='yes' || $_json['wa_queue_invoice_paid']=='true' || $_json['wa_queue_invoice_paid']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="invoice_paid" data-target="invoice_paid">Test Send</button>
                            <span class="help-block" id="wa_test_status_invoice_paid"></span>
                            <textarea class="form-control" id="invoice_paid" name="invoice_paid"
                                placeholder="{Lang::T('Hello')} [[name]], {Lang::T('your internet package')} [[package]] {Lang::T('has been expired')}"
                                rows="20">{Lang::htmlspecialchars($_json['invoice_paid'])}</textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[company_name]]</b> {Lang::T('Your Company Name at Settings')}.<br>
                            <b>[[address]]</b> {Lang::T('Your Company Address at Settings')}.<br>
                            <b>[[phone]]</b> - {Lang::T('Your Company Phone at Settings')}.<br>
                            <b>[[invoice]]</b> - {Lang::T('Invoice number')}.<br>
                            <b>[[date]]</b> - {Lang::T('Date invoice created')}.<br>
                            <b>[[payment_gateway]]</b> - {Lang::T('Payment gateway user paid from')}.<br>
                            <b>[[payment_channel]]</b> - {Lang::T('Payment channel user paid from')}.<br>
                            <b>[[type]]</b> - {Lang::T('is Hotspot or PPPOE')}.<br>
                            <b>[[plan_name]]</b> - {Lang::T('Internet Package')}.<br>
                            <b>[[plan_price]]</b> - {Lang::T('Internet Package Prices')}.<br>
                            <b>[[name]]</b> - {Lang::T('Receiver name')}.<br>
                            <b>[[user_name]]</b> - {Lang::T('Username internet')}.<br>
                            <b>[[user_password]]</b> - {Lang::T('User password')}.<br>
                            <b>[[expired_date]]</b> - {Lang::T('Expired datetime')}.<br>
                            <b>[[footer]]</b> - {Lang::T('Invoice Footer')}.<br>
                            <b>[[note]]</b> - {Lang::T('For Notes by admin')}.<br>
                            <b>[[invoice_link]]</b> - <a href="{$app_url}/docs/#Reminder%20with%20payment%20link"
                                target="_blank">{Lang::T("read documentation")}</a>.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Balance Notification Payment')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_invoice_balance" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_invoice_balance" value="1"
                                        {if isset($_json['wa_queue_invoice_balance']) && ($_json['wa_queue_invoice_balance']=='1' || $_json['wa_queue_invoice_balance']=='yes' || $_json['wa_queue_invoice_balance']=='true' || $_json['wa_queue_invoice_balance']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="invoice_balance" data-target="invoice_balance">Test Send</button>
                            <span class="help-block" id="wa_test_status_invoice_balance"></span>
                            <textarea class="form-control" id="invoice_balance" name="invoice_balance"
                                placeholder="{Lang::T('Hello')} [[name]], {Lang::T('your internet package')} [[package]] {Lang::T('has been expired')}"
                                rows="20">{Lang::htmlspecialchars($_json['invoice_balance'])}</textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[company_name]]</b> - {Lang::T('Your Company Name at Settings')}.<br>
                            <b>[[address]]</b> - {Lang::T('Your Company Address at Settings')}.<br>
                            <b>[[phone]]</b> - {Lang::T('Your Company Phone at Settings')}.<br>
                            <b>[[invoice]]</b> - {Lang::T('Invoice number')}.<br>
                            <b>[[date]]</b> - {Lang::T('Date invoice created')}.<br>
                            <b>[[payment_gateway]]</b> - {Lang::T('Payment gateway user paid from')}.<br>
                            <b>[[payment_channel]]</b> - {Lang::T('Payment channel user paid from')}.<br>
                            <b>[[type]]</b> - {Lang::T('is Hotspot or PPPOE')}.<br>
                            <b>[[plan_name]]</b> - {Lang::T('Internet Package')}.<br>
                            <b>[[plan_price]]</b> - {Lang::T('Internet Package Prices')}.<br>
                            <b>[[name]]</b> - {Lang::T('Receiver name')}.<br>
                            <b>[[user_name]]</b> - {Lang::T('Username internet')}.<br>
                            <b>[[user_password]]</b> - {Lang::T('User password')}.<br>
                            <b>[[trx_date]]</b> - {Lang::T('Transaction datetime')}.<br>
                            <b>[[balance_before]]</b> - {Lang::T('Balance Before')}.<br>
                            <b>[[balance]]</b> - {Lang::T('Balance After')}.<br>
                            <b>[[footer]]</b> - {Lang::T('Invoice Footer')}.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Welcome Message')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_welcome_message" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_welcome_message" value="1"
                                        {if isset($_json['wa_queue_welcome_message']) && ($_json['wa_queue_welcome_message']=='1' || $_json['wa_queue_welcome_message']=='yes' || $_json['wa_queue_welcome_message']=='true' || $_json['wa_queue_welcome_message']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="welcome_message" data-target="welcome_message">Test Send</button>
                            <span class="help-block" id="wa_test_status_welcome_message"></span>
                            <textarea class="form-control" id="welcome_message" name="welcome_message"
                                rows="4">{Lang::htmlspecialchars($_json['welcome_message'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[password]]</b> - {Lang::T('will be replaced with Customer password')}.<br>
                            <b>[[url]]</b> - {Lang::T('will be replaced with Customer Portal URL')}.<br>
                            <b>[[company]]</b> - {Lang::T('will be replaced with Company Name')}.<br>
                        </p>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Change Notification')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_plan_change_message" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_plan_change_message" value="1"
                                        {if isset($_json['wa_queue_plan_change_message']) && ($_json['wa_queue_plan_change_message']=='1' || $_json['wa_queue_plan_change_message']=='yes' || $_json['wa_queue_plan_change_message']=='true' || $_json['wa_queue_plan_change_message']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="plan_change_message" data-target="plan_change_message">Test Send</button>
                            <span class="help-block" id="wa_test_status_plan_change_message"></span>
                            <textarea class="form-control" id="plan_change_message" name="plan_change_message"
                                placeholder="{Lang::T('Great news')}, [[name]]! {Lang::T('Your plan has been successfully upgraded from ')} [[old_plan]] {Lang::T('to')} [[new_plan]]. {Lang::T('You can now enjoy seamless internet access until')} [[expiry]]. {Lang::T('Thank you for choosing')}  [[company]]  {Lang::T('for your internet needs')}, {Lang::T('Enjoy enhanced features and benefits starting today')}!"
                                rows="4">{if $_json['plan_change_message']!=''}{Lang::htmlspecialchars($_json['plan_change_message'])}{else}{Lang::T('Great news')}, [[name]]! {Lang::T('Your plan has been successfully upgraded from ')} [[old_plan]] {Lang::T('to')} [[new_plan]]. {Lang::T('You can now enjoy seamless internet access until')} [[expiry]]. {Lang::T('Thank
                                you for choosing')} [[company]] {Lang::T('for your internet needs')}, {Lang::T('Enjoy enhanced features and benefits starting today')}!{/if}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[old_plan]]</b> - {Lang::T('will be replaced with old plan name')}.<br>
                            <b>[[new_plan]]</b> - {Lang::T('will be replaced with new plan name')}.<br>
                            <b>[[expiry]]</b> - {Lang::T('will be replaced with the expiry date of the plan')}.<br>
                            <b>[[company]]</b> - {Lang::T('will be replaced with Company Name')}.<br>
                        </p>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Expiry Edit Notification')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_edit_expiry_message" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_edit_expiry_message" value="1"
                                        {if isset($_json['wa_queue_edit_expiry_message']) && ($_json['wa_queue_edit_expiry_message']=='1' || $_json['wa_queue_edit_expiry_message']=='yes' || $_json['wa_queue_edit_expiry_message']=='true' || $_json['wa_queue_edit_expiry_message']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="edit_expiry_message" data-target="edit_expiry_message">Test Send</button>
                            <span class="help-block" id="wa_test_status_edit_expiry_message"></span>
                            <textarea class="form-control" id="edit_expiry_message" name="edit_expiry_message"
                                placeholder="{Lang::T('Dear')} [[name]], {Lang::T('your')} [[plan]] {Lang::T('expiry date has been extended! You can now enjoy seamless internet access until')} [[expiry]]. {Lang::T('Thank you for choosing')}  [[company]]  {Lang::T('for your internet needs')}!"
                                rows="4">{if $_json['edit_expiry_message']!=''}{Lang::htmlspecialchars($_json['edit_expiry_message'])}{else}{Lang::T('Dear')} [[name]], {Lang::T('your')} [[plan]] {Lang::T('expiry date has been extended! You can now enjoy
                                seamless internet access until')} [[expiry]]. {Lang::T('Thank you for choosing')} [[company]] {Lang::T('for your
                                internet needs')}! {/if}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            <b>[[name]]</b> - {Lang::T('will be replaced with Customer Name')}.<br>
                            <b>[[username]]</b> - {Lang::T('will be replaced with Customer username')}.<br>
                            <b>[[plan]]</b> - {Lang::T('will be replaced with plan name')}.<br>
                            <b>[[expiry]]</b> - {Lang::T('will be replaced with the expiry date of the plan')}.<br>
                            <b>[[company]]</b> - {Lang::T('will be replaced with Company Name')}.<br>
                        </p>
                    </div>
                </div>
                {if $_c['enable_balance'] == 'yes'}
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Send Balance')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_balance_send" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_balance_send" value="1"
                                        {if isset($_json['wa_queue_balance_send']) && ($_json['wa_queue_balance_send']=='1' || $_json['wa_queue_balance_send']=='yes' || $_json['wa_queue_balance_send']=='true' || $_json['wa_queue_balance_send']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="balance_send" data-target="balance_send">Test Send</button>
                            <span class="help-block" id="wa_test_status_balance_send"></span>
                            <textarea class="form-control" id="balance_send" name="balance_send"
                                rows="4">{if $_json['balance_send']}{Lang::htmlspecialchars($_json['balance_send'])}{else}{Lang::htmlspecialchars($_default['balance_send'])}{/if}</textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[name]]</b> - {Lang::T('Receiver name')}.<br>
                            <b>[[balance]]</b> - {Lang::T('how much balance have been send')}.<br>
                            <b>[[current_balance]]</b> - {Lang::T('Current Balance')}.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Received Balance')}</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <input type="hidden" name="wa_queue_balance_received" value="0">
                                <label>
                                    <input type="checkbox" name="wa_queue_balance_received" value="1"
                                        {if isset($_json['wa_queue_balance_received']) && ($_json['wa_queue_balance_received']=='1' || $_json['wa_queue_balance_received']=='yes' || $_json['wa_queue_balance_received']=='true' || $_json['wa_queue_balance_received']=='on')}checked{/if}>
                                    WA Queue
                                </label>
                            </div>
                            <button type="button" class="btn btn-default btn-xs wa-template-test" data-template="balance_received" data-target="balance_received">Test Send</button>
                            <span class="help-block" id="wa_test_status_balance_received"></span>
                            <textarea class="form-control" id="balance_received" name="balance_received"
                                rows="4">{if $_json['balance_received']}{Lang::htmlspecialchars($_json['balance_received'])}{else}{Lang::htmlspecialchars($_default['balance_received'])}{/if}</textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[name]]</b> - {Lang::T('Sender name')}.<br>
                            <b>[[balance]]</b> - {Lang::T('how much balance have been received')}.<br>
                            <b>[[current_balance]]</b> - {Lang::T('Current Balance')}.
                        </p>
                    </div>
                </div>
                {/if}
                {* <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('PDF Invoice Template')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="email_invoice" name="email_invoice"
                                placeholder="{Lang::T('Template for sending pdf invoice')}" rows="20">
                            {if !empty($_json['email_invoice'])}
                            {Lang::htmlspecialchars($_json['email_invoice'])}
                            {else}
                            {Lang::htmlspecialchars($_default['email_invoice'])}
                            {/if}
                        </textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[company_name]]</b> {Lang::T('Your Company Name at Settings')}.<br>
                            <b>[[company_address]]</b> {Lang::T('Your Company Address at Settings')}.<br>
                            <b>[[company_phone]]</b> - {Lang::T('Your Company Phone at Settings')}.<br>
                            <b>[[invoice]]</b> - {Lang::T('Invoice number')}.<br>
                            <b>[[created_at]]</b> - {Lang::T('Date invoice created')}.<br>
                            <b>[[payment_gateway]]</b> - {Lang::T('Payment gateway user paid from')}.<br>
                            <b>[[payment_channel]]</b> - {Lang::T('Payment channel user paid from')}.<br>
                            <b>[[bill_rows]]</b> - {Lang::T('Bills table, where bills are listed')}.<br>
                            <b>[[currency]]</b> - {Lang::T('Your currency code at localisation Settings')}.<br>
                            <b>[[status]]</b> - {Lang::T('Invoice status')}.<br>
                            <b>[[fullname]]</b> - {Lang::T('Receiver name')}.<br>
                            <b>[[user_name]]</b> - {Lang::T('Username internet')}.<br>
                            <b>[[email]]</b> - {Lang::T('Customer email')} .<br>
                            <b>[[phone]]</b> - {Lang::T('Customer phone')}. <br>
                            <b>[[address]]</b> - {Lang::T('Customer phone')}. <br>
                            <b>[[expired_date]]</b> - {Lang::T('Expired datetime')}.<br>
                            <b>[[logo]]</b> - {Lang::T('Your company logo at Settings')}.<br>
                            <b>[[due_date]]</b> - {Lang::T('Invoice Due date, 7 Days after invoice created')}.<br>
                            <b>[[payment_link]]</b> - <a href="{$app_url}/docs/#Reminder%20with%20payment%20link"
                                target="_blank">{Lang::T("read documentation")}</a>.
                        </p>
                    </div>
                </div> *}

                <div class="panel-body">
                    <div class="form-group">
                        <button class="btn btn-success btn-block" type="submit">{Lang::T('Save Changes')}</button>
                    </div>
                </div>
            </div>
	    </div>
	</form>

		<script>
		(function () {
		    function byId(id) {
		        return document.getElementById(id);
		    }

			    var templateOverrides = {$template_overrides_json|default:'{}'};
			    if (!templateOverrides || typeof templateOverrides !== 'object') {
			        templateOverrides = {};
			    }

			    var overrideTypePostUrl = '{Text::url('settings/notifications-override-type-post')}';
			    var overridePlanPostUrl = '{Text::url('settings/notifications-override-plan-post')}';
			    var overridePurposePostUrl = '{Text::url('settings/notifications-override-purpose-post')}';

		    function getCsrfToken() {
		        var csrfInput = document.querySelector('input[name="csrf_token"]');
		        return csrfInput ? (csrfInput.value || '') : '';
		    }

		    function postForm(url, fields) {
		        if (!url) return;
		        var form = document.createElement('form');
		        form.method = 'POST';
		        form.action = url;
		        Object.keys(fields || {}).forEach(function (key) {
		            var input = document.createElement('input');
		            input.type = 'hidden';
		            input.name = key;
		            input.value = fields[key] == null ? '' : String(fields[key]);
		            form.appendChild(input);
		        });
		        document.body.appendChild(form);
		        form.submit();
		    }

				    function normalizeTypeKey(value) {
				        return String(value || '').trim().toUpperCase();
				    }

				    function normalizePurposeKey(value) {
				        return String(value || '').trim().toLowerCase();
				    }

				    var overrideCapabilities = {
				        // Plan-related templates: can override per category or per plan.
				        expired: { scopes: { type: true, plan: true } },
				        reminder_7_day: { scopes: { type: true, plan: true } },
				        reminder_3_day: { scopes: { type: true, plan: true } },
				        reminder_1_day: { scopes: { type: true, plan: true } },
				        invoice_paid: { scopes: { type: true, plan: true } },
				        edit_expiry_message: { scopes: { type: true, plan: true } },

				        // Purpose-based templates: can override per purpose only.
				        otp_message: {
				            scopes: { purpose: true },
				            purposes: [
				                { key: 'register', label: 'Register' },
				                { key: 'verify', label: 'Verify' },
				                { key: 'forgot', label: 'Forgot' }
				            ]
				        },
				        welcome_message: {
				            scopes: { purpose: true },
				            purposes: [
				                { key: 'admin_register', label: 'Admin Register' },
				                { key: 'self_register', label: 'Self Register' }
				            ]
				        }
				    };

				    function getOverrideCapability(templateKey) {
				        templateKey = String(templateKey || '').trim();
				        return overrideCapabilities.hasOwnProperty(templateKey) ? overrideCapabilities[templateKey] : null;
				    }

				    function isOverrideSupportedTemplate(templateKey) {
				        return !!getOverrideCapability(templateKey);
				    }

				    function isOverrideSupportedForScope(templateKey, scope) {
				        var cap = getOverrideCapability(templateKey);
				        if (!cap || !cap.scopes || typeof cap.scopes !== 'object') return false;
				        return !!cap.scopes[String(scope || '').trim()];
				    }

				    function listSupportedScopesForTemplate(templateKey) {
				        var cap = getOverrideCapability(templateKey);
				        if (!cap || !cap.scopes || typeof cap.scopes !== 'object') return [];
				        var out = [];
				        Object.keys(cap.scopes).forEach(function (scope) {
				            if (cap.scopes[scope]) out.push(scope);
				        });
				        return out;
				    }

				    function applyBuilderOverrideSupport(templateKey) {
				        var supported = isOverrideSupportedTemplate(templateKey);
				        var enabledRow = byId('wa_builder_override_enabled_row');
				        var enabledCheckbox = byId('wa_builder_override_enabled');
				        var enabledHint = byId('wa_builder_override_enabled_hint');
				        if (enabledRow) enabledRow.style.display = supported ? '' : 'none';
				        if (enabledHint) {
				            enabledHint.textContent = '';
				            if (supported) {
				                var scopes = listSupportedScopesForTemplate(templateKey);
					                if (scopes.indexOf('purpose') !== -1 && scopes.length === 1) {
					                    enabledHint.textContent = '(Per Purpose)';
					                } else if (scopes.indexOf('type') !== -1 || scopes.indexOf('plan') !== -1) {
					                    enabledHint.textContent = '(Per Category / Per Plan)';
					                }
					            }
					        }
				        if (enabledCheckbox) {
				            if (!supported) {
				                enabledCheckbox.checked = false;
				                enabledCheckbox.disabled = true;
				            } else {
				                enabledCheckbox.disabled = false;
				            }
				        }
				        // Make sure dependent override rows/button labels are reset when unsupported.
				        if (!supported) {
				            var scopeRow = byId('wa_builder_override_scope_row');
				            var typeRow = byId('wa_builder_override_type_row');
				            var planRow = byId('wa_builder_override_plan_row');
				            var purposeRow = byId('wa_builder_override_purpose_row');
				            if (scopeRow) scopeRow.style.display = 'none';
				            if (typeRow) typeRow.style.display = 'none';
				            if (planRow) planRow.style.display = 'none';
				            if (purposeRow) purposeRow.style.display = 'none';
				            var insertBtn = byId('wa_builder_insert');
				            if (insertBtn) insertBtn.textContent = 'Insert to Target';
				        }
				    }

		    function getOverrideForType(templateKey, typeKey) {
		        templateKey = String(templateKey || '').trim();
		        typeKey = normalizeTypeKey(typeKey);
		        if (!templateKey || !typeKey) return '';
		        if (!templateOverrides.type || typeof templateOverrides.type !== 'object') return '';
		        var byType = templateOverrides.type[typeKey];
		        if (!byType || typeof byType !== 'object') return '';
		        var val = byType[templateKey];
		        return (typeof val === 'string') ? val : '';
		    }

		    function getOverrideForPlan(templateKey, planId) {
		        templateKey = String(templateKey || '').trim();
		        planId = String(planId || '').trim();
		        if (!templateKey || !planId) return '';
		        if (!templateOverrides.plan || typeof templateOverrides.plan !== 'object') return '';
		        var byPlan = templateOverrides.plan[planId];
		        if (!byPlan || typeof byPlan !== 'object') return '';
		        var val = byPlan[templateKey];
		        return (typeof val === 'string') ? val : '';
		    }

		    function getOverrideForPurpose(templateKey, purposeKey) {
		        templateKey = String(templateKey || '').trim();
		        purposeKey = normalizePurposeKey(purposeKey);
		        if (!templateKey || !purposeKey) return '';
		        if (!templateOverrides.purpose || typeof templateOverrides.purpose !== 'object') return '';
		        var byPurpose = templateOverrides.purpose[purposeKey];
		        if (!byPurpose || typeof byPurpose !== 'object') return '';
		        var val = byPurpose[templateKey];
		        return (typeof val === 'string') ? val : '';
		    }

		    function getPurposeOptionsForTemplate(templateKey) {
		        var cap = getOverrideCapability(templateKey);
		        if (!cap || !cap.purposes || !Array.isArray(cap.purposes)) return [];
		        return cap.purposes;
		    }

		    function getPurposeLabel(templateKey, purposeKey) {
		        purposeKey = normalizePurposeKey(purposeKey);
		        if (!purposeKey) return '';
		        var opts = getPurposeOptionsForTemplate(templateKey);
		        for (var i = 0; i < opts.length; i++) {
		            if (normalizePurposeKey(opts[i].key) === purposeKey) {
		                return opts[i].label || purposeKey;
		            }
		        }
		        return purposeKey;
		    }

		    function listTypeOverridesForTemplate(templateKey) {
		        templateKey = String(templateKey || '').trim();
		        if (!templateKey) return [];
		        var out = [];
		        ['HOTSPOT', 'PPPOE', 'VPN'].forEach(function (t) {
		            var val = getOverrideForType(templateKey, t);
		            if (typeof val === 'string' && val.trim() !== '') {
		                out.push(t);
		            }
		        });
		        return out;
		    }

		    function listPlanOverridesForTemplate(templateKey) {
		        templateKey = String(templateKey || '').trim();
		        if (!templateKey) return [];
		        if (!templateOverrides.plan || typeof templateOverrides.plan !== 'object') return [];
		        var out = [];
		        Object.keys(templateOverrides.plan).forEach(function (planId) {
		            var byPlan = templateOverrides.plan[planId];
		            if (!byPlan || typeof byPlan !== 'object') return;
		            var val = byPlan[templateKey];
		            if (typeof val === 'string' && val.trim() !== '') {
		                out.push(planId);
		            }
		        });
		        out.sort(function (a, b) {
		            var ai = parseInt(a, 10);
		            var bi = parseInt(b, 10);
		            if (isNaN(ai) || isNaN(bi)) return String(a).localeCompare(String(b));
		            return ai - bi;
		        });
		        return out;
		    }

		    function listPurposeOverridesForTemplate(templateKey) {
		        templateKey = String(templateKey || '').trim();
		        if (!templateKey) return [];
		        if (!templateOverrides.purpose || typeof templateOverrides.purpose !== 'object') return [];
		        if (!isOverrideSupportedForScope(templateKey, 'purpose')) return [];

		        var out = [];
		        var seen = {};

		        var opts = getPurposeOptionsForTemplate(templateKey);
		        if (opts.length) {
		            opts.forEach(function (opt) {
		                var key = normalizePurposeKey(opt.key);
		                if (!key || seen[key]) return;
		                var val = getOverrideForPurpose(templateKey, key);
		                if (typeof val === 'string' && val.trim() !== '') {
		                    seen[key] = true;
		                    out.push(key);
		                }
		            });
		        }

		        var extra = [];
		        Object.keys(templateOverrides.purpose).forEach(function (purposeKey) {
		            var key = normalizePurposeKey(purposeKey);
		            if (!key || seen[key]) return;
		            var val = getOverrideForPurpose(templateKey, key);
		            if (typeof val === 'string' && val.trim() !== '') {
		                seen[key] = true;
		                extra.push(key);
		            }
		        });
		        extra.sort();
		        return out.concat(extra);
		    }

			    function getPlanLabel(planId) {
			        planId = String(planId || '').trim();
			        if (!planId) return '';
			        var sel = byId('wa_builder_override_plan');
		        if (!sel) return '#' + planId;
		        for (var i = 0; i < sel.options.length; i++) {
		            if (String(sel.options[i].value) === planId) {
		                return sel.options[i].text || ('#' + planId);
		            }
		        }
			        return '#' + planId;
			    }

			    function getAllPlanIds() {
			        var sel = byId('wa_builder_override_plan');
			        if (!sel) return [];
			        var out = [];
			        var seen = {};
			        for (var i = 0; i < sel.options.length; i++) {
			            var v = String(sel.options[i].value || '').trim();
			            var n = parseInt(v, 10);
			            if (!n || n < 1) continue;
			            var key = String(n);
			            if (seen[key]) continue;
			            seen[key] = true;
			            out.push(key);
			        }
			        return out;
			    }

			    function getPlanTypeKey(planId) {
			        planId = String(planId || '').trim();
			        if (!planId) return '';
			        var sel = byId('wa_builder_override_plan');
			        if (!sel) return '';
			        for (var i = 0; i < sel.options.length; i++) {
			            if (String(sel.options[i].value) !== planId) continue;
			            var rawType = sel.options[i].getAttribute('data-type') || '';
			            if (!rawType) {
			                // Fallback: parse from "#id - TYPE - name" label if present.
			                var text = String(sel.options[i].text || '');
			                var parts = text.split(' - ');
			                if (parts.length >= 3) rawType = parts[1];
			            }
			            return normalizeTypeKey(rawType);
			        }
			        return '';
			    }

			    function resolveTemplateForType(templateKey, typeKey) {
			        templateKey = String(templateKey || '').trim();
			        typeKey = normalizeTypeKey(typeKey);
			        var overrideText = getOverrideForType(templateKey, typeKey);
			        if (typeof overrideText === 'string' && overrideText.trim() !== '') {
			            return { text: overrideText, source: 'type', typeKey: typeKey };
			        }
			        return { text: getTemplateTextareaValue(templateKey), source: 'global', typeKey: typeKey };
			    }

			    function resolveTemplateForPlan(templateKey, planId) {
			        templateKey = String(templateKey || '').trim();
			        planId = String(planId || '').trim();
			        var planOverride = getOverrideForPlan(templateKey, planId);
			        if (typeof planOverride === 'string' && planOverride.trim() !== '') {
			            return { text: planOverride, source: 'plan', planId: planId, typeKey: '' };
			        }
			        var typeKey = getPlanTypeKey(planId);
			        if (typeKey) {
			            var typeOverride = getOverrideForType(templateKey, typeKey);
			            if (typeof typeOverride === 'string' && typeOverride.trim() !== '') {
			                return { text: typeOverride, source: 'type', planId: planId, typeKey: typeKey };
			            }
			        }
			        return { text: getTemplateTextareaValue(templateKey), source: 'global', planId: planId, typeKey: typeKey };
			    }

				    function isBuilderOverrideEnabled() {
				        var el = byId('wa_builder_override_enabled');
				        return !!(el && el.checked);
				    }

		    function getBuilderTemplateKey() {
		        var sel = byId('wa_builder_target');
		        return sel ? String(sel.value || '').trim() : '';
		    }

		    function getBuilderOverrideScope() {
		        var templateKey = getBuilderTemplateKey();
		        var scopes = listSupportedScopesForTemplate(templateKey);
		        if (scopes.indexOf('purpose') !== -1 && scopes.length === 1) {
		            return 'purpose';
		        }

		        var sel = byId('wa_builder_override_scope');
		        var val = sel ? (sel.value || '') : '';
		        return (val === 'plan') ? 'plan' : 'type';
		    }

		    function getBuilderOverrideTypeKey() {
		        var sel = byId('wa_builder_override_type');
		        return sel ? (sel.value || '') : '';
		    }

		    function getBuilderOverridePlanId() {
		        var sel = byId('wa_builder_override_plan');
		        return sel ? (sel.value || '') : '';
		    }

		    function getBuilderOverridePurposeKey() {
		        var sel = byId('wa_builder_override_purpose');
		        return sel ? (sel.value || '') : '';
		    }

		    function refreshBuilderPurposeOptions(templateKey) {
		        var sel = byId('wa_builder_override_purpose');
		        if (!sel) return;
		        templateKey = String(templateKey || '').trim();
		        var opts = getPurposeOptionsForTemplate(templateKey);
		        var prev = normalizePurposeKey(sel.value || '');
		        sel.innerHTML = '';
		        if (!opts.length) return;
		        opts.forEach(function (opt) {
		            var key = normalizePurposeKey(opt.key);
		            if (!key) return;
		            var o = document.createElement('option');
		            o.value = key;
		            o.textContent = opt.label || key;
		            sel.appendChild(o);
		        });
		        if (prev) {
		            for (var i = 0; i < sel.options.length; i++) {
		                if (normalizePurposeKey(sel.options[i].value) === prev) {
		                    sel.value = sel.options[i].value;
		                    return;
		                }
		            }
		        }
		        sel.value = sel.options.length ? sel.options[0].value : '';
		    }

		    function toggleBuilderOverrideUi() {
		        var enabled = isBuilderOverrideEnabled();
		        var scopeRow = byId('wa_builder_override_scope_row');
		        var typeRow = byId('wa_builder_override_type_row');
		        var planRow = byId('wa_builder_override_plan_row');
		        var purposeRow = byId('wa_builder_override_purpose_row');
		        var insertBtn = byId('wa_builder_insert');

		        var templateKey = getBuilderTemplateKey();
		        var scope = getBuilderOverrideScope();

		        if (scopeRow) {
		            scopeRow.style.display = (enabled && scope !== 'purpose') ? '' : 'none';
		        }
		        if (typeRow) typeRow.style.display = (enabled && scope === 'type') ? '' : 'none';
		        if (planRow) planRow.style.display = (enabled && scope === 'plan') ? '' : 'none';
		        if (purposeRow) purposeRow.style.display = (enabled && scope === 'purpose') ? '' : 'none';

		        if (enabled && scope === 'purpose') {
		            refreshBuilderPurposeOptions(templateKey);
		        }

		        if (insertBtn) {
		            insertBtn.textContent = enabled ? 'Save' : 'Insert to Target';
		        }
		    }

		    function getTemplateTextareaValue(templateKey) {
		        templateKey = String(templateKey || '').trim();
		        if (!templateKey) return '';
		        var target = byId(templateKey);
		        return target ? (target.value || '') : '';
		    }

			    function getSelectedOverrideValue(templateKey) {
			        if (!isBuilderOverrideEnabled()) return null;
			        var scope = getBuilderOverrideScope();
			        if (scope === 'purpose') {
			            var purposeKey = getBuilderOverridePurposeKey();
			            if (!purposeKey) return null;
			            var pv = getOverrideForPurpose(templateKey, purposeKey);
			            return (typeof pv === 'string' && pv.trim() !== '') ? pv : null;
			        }
			        if (scope === 'type') {
			            var typeKey = getBuilderOverrideTypeKey();
			            if (!typeKey) return null;
			            var v = getOverrideForType(templateKey, typeKey);
			            return (typeof v === 'string' && v.trim() !== '') ? v : null;
			        }
			        var planId = getBuilderOverridePlanId();
			        if (!planId) return null;
			        var p = getOverrideForPlan(templateKey, planId);
			        return (typeof p === 'string' && p.trim() !== '') ? p : null;
			    }

				    function getTargetLabel(templateKey) {
				        templateKey = String(templateKey || '').trim();
				        if (!templateKey) return '';
				        var sel = byId('wa_builder_target');
				        if (!sel) return templateKey;
				        for (var i = 0; i < sel.options.length; i++) {
				            if (String(sel.options[i].value || '') === templateKey) {
				                return (sel.options[i].text || templateKey).trim();
				            }
				        }
				        return templateKey;
				    }

					    function listTemplatesWithTypeOverrides() {
					        var seen = {};
					        if (!templateOverrides.type || typeof templateOverrides.type !== 'object') return [];
					        Object.keys(templateOverrides.type).forEach(function (typeKey) {
					            var byType = templateOverrides.type[typeKey];
					            if (!byType || typeof byType !== 'object') return;
					            Object.keys(byType).forEach(function (templateKey) {
					                var val = byType[templateKey];
					                if (typeof val !== 'string' || val.trim() === '') return;
					                if (!isOverrideSupportedForScope(templateKey, 'type')) return;
					                seen[String(templateKey)] = true;
					            });
					        });
					        return Object.keys(seen).sort(function (a, b) {
					            return getTargetLabel(a).localeCompare(getTargetLabel(b));
					        });
					    }

					    function listTemplatesWithPlanOverrides() {
					        var seen = {};
					        if (!templateOverrides.plan || typeof templateOverrides.plan !== 'object') return [];
					        Object.keys(templateOverrides.plan).forEach(function (planId) {
					            var byPlan = templateOverrides.plan[planId];
					            if (!byPlan || typeof byPlan !== 'object') return;
					            Object.keys(byPlan).forEach(function (templateKey) {
					                var val = byPlan[templateKey];
					                if (typeof val !== 'string' || val.trim() === '') return;
					                if (!isOverrideSupportedForScope(templateKey, 'plan')) return;
					                seen[String(templateKey)] = true;
					            });
					        });
					        return Object.keys(seen).sort(function (a, b) {
					            return getTargetLabel(a).localeCompare(getTargetLabel(b));
					        });
					    }

					    function listTemplatesWithPurposeOverrides() {
					        var seen = {};
					        if (!templateOverrides.purpose || typeof templateOverrides.purpose !== 'object') return [];
					        Object.keys(templateOverrides.purpose).forEach(function (purposeKey) {
					            var byPurpose = templateOverrides.purpose[purposeKey];
					            if (!byPurpose || typeof byPurpose !== 'object') return;
					            Object.keys(byPurpose).forEach(function (templateKey) {
					                var val = byPurpose[templateKey];
					                if (typeof val !== 'string' || val.trim() === '') return;
					                if (!isOverrideSupportedForScope(templateKey, 'purpose')) return;
					                seen[String(templateKey)] = true;
					            });
					        });
					        return Object.keys(seen).sort(function (a, b) {
					            return getTargetLabel(a).localeCompare(getTargetLabel(b));
					        });
					    }

					    function refreshTemplatePreviewPanel() {
					        var modeSelect = byId('wa_template_preview_mode');
					        var targetRow = byId('wa_template_preview_target_row');
					        var targetSelect = byId('wa_template_preview_target');
					        var typeRow = byId('wa_template_preview_type_row');
					        var planRow = byId('wa_template_preview_plan_row');
					        var purposeRow = byId('wa_template_preview_purpose_row');
					        var typeSelect = byId('wa_template_preview_type');
					        var planSelect = byId('wa_template_preview_plan');
					        var purposeSelect = byId('wa_template_preview_purpose');
					        var textArea = byId('wa_template_preview_text');
					        var hint = byId('wa_template_preview_hint');
					        var delBtn = byId('wa_template_preview_delete');
					        var editBtn = byId('wa_template_preview_edit');

					        if (!modeSelect || !textArea) return;

					        var templatesType = listTemplatesWithTypeOverrides();
					        var templatesPlan = listTemplatesWithPlanOverrides();
					        var templatesPurpose = listTemplatesWithPurposeOverrides();
					        var hasType = templatesType.length > 0;
					        var hasPlan = templatesPlan.length > 0;
					        var hasPurpose = templatesPurpose.length > 0;

					        var typeOpt = modeSelect.querySelector('option[value=\"type\"]');
					        var planOpt = modeSelect.querySelector('option[value=\"plan\"]');
					        var purposeOpt = modeSelect.querySelector('option[value=\"purpose\"]');
					        if (typeOpt) {
					            typeOpt.disabled = !hasType;
					            typeOpt.hidden = !hasType;
					        }
					        if (planOpt) {
					            planOpt.disabled = !hasPlan;
					            planOpt.hidden = !hasPlan;
					        }
					        if (purposeOpt) {
					            purposeOpt.disabled = !hasPurpose;
					            purposeOpt.hidden = !hasPurpose;
					        }

					        if (!hasType && !hasPlan && !hasPurpose) {
					            modeSelect.disabled = true;
					            if (targetRow) targetRow.style.display = 'none';
					            if (typeRow) typeRow.style.display = 'none';
					            if (planRow) planRow.style.display = 'none';
					            if (purposeRow) purposeRow.style.display = 'none';
					            textArea.value = '';
					            if (hint) hint.textContent = 'No overrides saved yet.';
					            if (delBtn) delBtn.style.display = 'none';
					            if (editBtn) editBtn.style.display = 'none';
					            return;
					        }

					        modeSelect.disabled = false;
					        var mode = modeSelect.value || '';
					        if (mode !== 'type' && mode !== 'plan' && mode !== 'purpose') {
					            mode = hasPlan ? 'plan' : (hasType ? 'type' : 'purpose');
					        }
					        if (mode === 'type' && !hasType) mode = 'plan';
					        if (mode === 'plan' && !hasPlan) mode = hasType ? 'type' : 'purpose';
					        if (mode === 'purpose' && !hasPurpose) mode = hasPlan ? 'plan' : 'type';
					        if (modeSelect.value !== mode) modeSelect.value = mode;

					        var templates = (mode === 'plan') ? templatesPlan : ((mode === 'purpose') ? templatesPurpose : templatesType);
					        if (targetSelect) {
					            var prevTarget = String(targetSelect.value || '').trim();
					            targetSelect.innerHTML = '';
					            templates.forEach(function (key) {
				                var opt = document.createElement('option');
				                opt.value = key;
				                opt.textContent = getTargetLabel(key) || key;
				                targetSelect.appendChild(opt);
				            });
				            if (prevTarget && templates.indexOf(prevTarget) !== -1) {
				                targetSelect.value = prevTarget;
				            } else if (templates.length) {
				                targetSelect.value = templates[0];
				            }
				        }

					        var templateKey = targetSelect ? String(targetSelect.value || '').trim() : (templates[0] || '');
					        if (targetRow) targetRow.style.display = templateKey ? '' : 'none';

					        if (!templateKey) {
					            if (typeRow) typeRow.style.display = 'none';
					            if (planRow) planRow.style.display = 'none';
					            if (purposeRow) purposeRow.style.display = 'none';
					            textArea.value = '';
					            if (hint) hint.textContent = 'No overrides saved yet.';
					            if (delBtn) delBtn.style.display = 'none';
					            if (editBtn) editBtn.style.display = 'none';
					            return;
					        }

					        if (typeRow) typeRow.style.display = (mode === 'type') ? '' : 'none';
					        if (planRow) planRow.style.display = (mode === 'plan') ? '' : 'none';
					        if (purposeRow) purposeRow.style.display = (mode === 'purpose') ? '' : 'none';

					        var previewText = '';
					        var canDelete = false;
					        var deletePayload = null;
					        var hintText = '';

					        if (mode === 'type') {
					            var typeKeys = listTypeOverridesForTemplate(templateKey);
				            if (typeSelect) {
				                var prevType = normalizeTypeKey(typeSelect.value || '');
				                typeSelect.innerHTML = '';
				                typeKeys.forEach(function (t) {
				                    var opt = document.createElement('option');
				                    opt.value = t;
				                    opt.textContent = t;
				                    typeSelect.appendChild(opt);
				                });
				                if (prevType && typeKeys.indexOf(prevType) !== -1) {
				                    typeSelect.value = prevType;
				                } else if (typeKeys.length) {
				                    typeSelect.value = typeKeys[0];
				                }
				            }
					            var typeKey = typeSelect ? normalizeTypeKey(typeSelect.value || '') : (typeKeys[0] || '');
					            previewText = getOverrideForType(templateKey, typeKey) || '';
					            hintText = 'Category override: ' + getTargetLabel(templateKey) + ' / ' + typeKey;
					            canDelete = previewText.trim() !== '';
					            deletePayload = canDelete ? { scope: 'type', template_key: templateKey, type_key: typeKey } : null;
					        } else if (mode === 'plan') {
					            var planIds = listPlanOverridesForTemplate(templateKey);
					            if (planSelect) {
					                var prevPlan = String(planSelect.value || '').trim();
					                planSelect.innerHTML = '';
				                planIds.forEach(function (pid) {
				                    var opt = document.createElement('option');
				                    opt.value = pid;
				                    opt.textContent = getPlanLabel(pid);
				                    planSelect.appendChild(opt);
				                });
				                if (prevPlan && planIds.indexOf(prevPlan) !== -1) {
				                    planSelect.value = prevPlan;
				                } else if (planIds.length) {
				                    planSelect.value = planIds[0];
				                }
					            }
					            var planId = planSelect ? String(planSelect.value || '').trim() : (planIds[0] || '');
					            previewText = getOverrideForPlan(templateKey, planId) || '';
					            hintText = 'Plan override: ' + getTargetLabel(templateKey) + ' / ' + getPlanLabel(planId);
					            canDelete = previewText.trim() !== '';
					            deletePayload = canDelete ? { scope: 'plan', template_key: templateKey, plan_id: planId } : null;
					        } else {
					            var purposeKeys = listPurposeOverridesForTemplate(templateKey);
					            if (purposeSelect) {
					                var prevPurpose = normalizePurposeKey(purposeSelect.value || '');
					                purposeSelect.innerHTML = '';
					                purposeKeys.forEach(function (pkey) {
					                    var opt = document.createElement('option');
					                    opt.value = pkey;
					                    opt.textContent = getPurposeLabel(templateKey, pkey);
					                    purposeSelect.appendChild(opt);
					                });
					                if (prevPurpose && purposeKeys.indexOf(prevPurpose) !== -1) {
					                    purposeSelect.value = prevPurpose;
					                } else if (purposeKeys.length) {
					                    purposeSelect.value = purposeKeys[0];
					                }
					            }
					            var purposeKey = purposeSelect ? normalizePurposeKey(purposeSelect.value || '') : (purposeKeys[0] || '');
					            previewText = getOverrideForPurpose(templateKey, purposeKey) || '';
					            hintText = 'Purpose override: ' + getTargetLabel(templateKey) + ' / ' + getPurposeLabel(templateKey, purposeKey);
					            canDelete = previewText.trim() !== '';
					            deletePayload = canDelete ? { scope: 'purpose', template_key: templateKey, purpose_key: purposeKey } : null;
					        }

					        textArea.value = previewText || '';
					        if (hint) hint.textContent = hintText;
				        if (delBtn) {
				            delBtn.style.display = canDelete ? '' : 'none';
				            delBtn.setAttribute('data-delete', deletePayload ? JSON.stringify(deletePayload) : '');
				        }
					        if (editBtn) {
					            editBtn.style.display = canDelete ? '' : 'none';
					            editBtn.setAttribute('data-edit', deletePayload ? JSON.stringify(deletePayload) : '');
					        }
					    }

			    function deletePreviewOverride() {
			        var delBtn = byId('wa_template_preview_delete');
			        if (!delBtn) return;
			        var raw = delBtn.getAttribute('data-delete') || '';
			        if (!raw) return;
			        var data = null;
			        try { data = JSON.parse(raw); } catch (e) { data = null; }
			        if (!data) return;

				        var templateKey = String(data.template_key || '').trim();
				        if (!templateKey) return;

					        var scopeLabel = '';
					        if (data.scope === 'plan') {
					            scopeLabel = 'plan ' + getPlanLabel(data.plan_id || '');
					        } else if (data.scope === 'type') {
					            scopeLabel = 'category ' + (data.type_key || '');
					        } else if (data.scope === 'purpose') {
					            scopeLabel = 'purpose ' + getPurposeLabel(templateKey, data.purpose_key || '');
					        }
				        if (!confirm('Delete override ' + scopeLabel + ' for template \"' + templateKey + '\"?')) {
				            return;
				        }

				        var csrf = getCsrfToken();
				        if (data.scope === 'type') {
			            postForm(overrideTypePostUrl, {
			                csrf_token: csrf,
			                template_key: templateKey,
			                type_key: data.type_key || '',
			                message: ''
		            });
		            return;
			        }
			        if (data.scope === 'purpose') {
			            postForm(overridePurposePostUrl, {
			                csrf_token: csrf,
			                template_key: templateKey,
			                purpose_key: data.purpose_key || '',
			                message: ''
			            });
			            return;
			        }
			        postForm(overridePlanPostUrl, {
			            csrf_token: csrf,
			            template_key: templateKey,
		            plan_id: data.plan_id || '',
		            message: ''
		        });
		    }

			    function editPreviewOverrideInBuilder() {
			        var editBtn = byId('wa_template_preview_edit');
			        if (!editBtn) return;
			        var raw = editBtn.getAttribute('data-edit') || '';
			        if (!raw) return;

			        var data = null;
			        try { data = JSON.parse(raw); } catch (e) { data = null; }
			        if (!data) return;

			        var templateKey = String(data.template_key || '').trim();
			        if (!templateKey) return;

			        var targetSelect = byId('wa_builder_target');
			        if (!targetSelect) return;

			        var foundTarget = false;
			        for (var i = 0; i < targetSelect.options.length; i++) {
			            if (String(targetSelect.options[i].value || '') === templateKey) {
			                foundTarget = true;
			                break;
			            }
			        }
			        if (!foundTarget) {
			            alert('Selected override target is not available in builder.');
			            return;
			        }

			        targetSelect.value = templateKey;
			        applyBuilderOverrideSupport(templateKey);

			        var overrideEnabled = byId('wa_builder_override_enabled');
			        if (overrideEnabled && !overrideEnabled.disabled) {
			            overrideEnabled.checked = true;
			        }

			        var scope = String(data.scope || '').trim().toLowerCase();
			        var scopeSelect = byId('wa_builder_override_scope');
			        if (scopeSelect && (scope === 'type' || scope === 'plan')) {
			            scopeSelect.value = scope;
			        }

			        if (scope === 'type') {
			            var typeSelect = byId('wa_builder_override_type');
			            var wantedType = normalizeTypeKey(data.type_key || '');
			            if (typeSelect && wantedType) {
			                for (var t = 0; t < typeSelect.options.length; t++) {
			                    if (normalizeTypeKey(typeSelect.options[t].value || '') === wantedType) {
			                        typeSelect.value = typeSelect.options[t].value;
			                        break;
			                    }
			                }
			            }
			        } else if (scope === 'plan') {
			            var planSelect = byId('wa_builder_override_plan');
			            var wantedPlan = String(data.plan_id || '').trim();
			            if (planSelect && wantedPlan) {
			                for (var p = 0; p < planSelect.options.length; p++) {
			                    if (String(planSelect.options[p].value || '') === wantedPlan) {
			                        planSelect.value = wantedPlan;
			                        break;
			                    }
			                }
			            }
			        } else if (scope === 'purpose') {
			            refreshBuilderPurposeOptions(templateKey);
			            var purposeSelect = byId('wa_builder_override_purpose');
			            var wantedPurpose = normalizePurposeKey(data.purpose_key || '');
			            if (purposeSelect && wantedPurpose) {
			                for (var x = 0; x < purposeSelect.options.length; x++) {
			                    if (normalizePurposeKey(purposeSelect.options[x].value || '') === wantedPurpose) {
			                        purposeSelect.value = purposeSelect.options[x].value;
			                        break;
			                    }
			                }
			            }
			        }

			        toggleBuilderOverrideUi();
			        autofillBuilderFromTarget();

			        var builderPanel = byId('wa_builder');
			        if (builderPanel && typeof builderPanel.scrollIntoView === 'function') {
			            builderPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
			        }
			        window.setTimeout(function () {
			            var focusEl = byId('wa_builder_text') || byId('wa_builder_preview');
			            if (focusEl && typeof focusEl.focus === 'function') {
			                focusEl.focus();
			            }
			        }, 180);
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
        var idInput = row.querySelector('.wa-builder-id');
        var textInput = row.querySelector('.wa-builder-text');
        if (idInput && idVal !== undefined && idVal !== null) idInput.value = idVal;
        if (textInput && textVal !== undefined && textVal !== null) textInput.value = textVal;
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
        var sectionInput = row.querySelector('.wa-builder-row-section');
        var idInput = row.querySelector('.wa-builder-row-id');
        var titleInput = row.querySelector('.wa-builder-row-title');
        var descInput = row.querySelector('.wa-builder-row-desc');
        if (sectionInput && sectionVal !== undefined && sectionVal !== null) sectionInput.value = sectionVal;
        if (idInput && idVal !== undefined && idVal !== null) idInput.value = idVal;
        if (titleInput && titleVal !== undefined && titleVal !== null) titleInput.value = titleVal;
        if (descInput && descVal !== undefined && descVal !== null) descInput.value = descVal;
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
	            '<option value="copy">copy</option>' +
	            '</select></div>' +
	            '<div class="col-xs-4"><input type="text" class="form-control wa-builder-template-text" placeholder="text"></div>' +
	            '<div class="col-xs-4"><input type="text" class="form-control wa-builder-template-value" placeholder="id/url/phone/code"></div>' +
	            '<div class="col-xs-1"><button type="button" class="btn btn-danger btn-xs wa-builder-remove">x</button></div>';
        container.appendChild(row);
        var typeInput = row.querySelector('.wa-builder-template-type');
        var textInput = row.querySelector('.wa-builder-template-text');
        var valueInput = row.querySelector('.wa-builder-template-value');
        if (typeInput && typeVal !== undefined && typeVal !== null) typeInput.value = typeVal;
        if (textInput && textVal !== undefined && textVal !== null) textInput.value = textVal;
        if (valueInput && valueVal !== undefined && valueVal !== null) valueInput.value = valueVal;
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
	        var mediaRow = byId('wa_builder_header_media') ? byId('wa_builder_header_media').closest('.form-group') : null;
	        var uploadRow = byId('wa_builder_header_media_file') ? byId('wa_builder_header_media_file').closest('.form-group') : null;

	        var showMedia = (headerType === '2' || headerType === '3' || headerType === '4');
	        if (mediaRow) mediaRow.style.display = showMedia ? '' : 'none';
	        if (uploadRow) uploadRow.style.display = showMedia ? '' : 'none';
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
	        if ((headerType === '' || headerType === '1') && headerText.trim() !== '') {
	            lines.push('[headerText](' + headerText.trim() + ')');
	        }
	        if ((headerType === '2' || headerType === '3' || headerType === '4') && headerMedia.trim() !== '') {
	            lines.push('[headerType](' + headerType + ')');
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
	                } else if (t === 'copy') {
	                    lines.push('[button](copy|' + (textVal.trim() || valueVal.trim()) + '|' + valueVal.trim() + ')');
	                }
	            });
	        }

        lines.push('[[/wa]]');
        return lines.join('\n');
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
        var match = text.match(/\[\[wa\]\]([\s\S]*?)\[\[\/wa\]\]/i);
        if (!match) return null;
        var block = match[1] || '';
        var lines = block.split(/\r?\n/);
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
                if (first === 'quick' || first === 'url' || first === 'call' || first === 'copy') {
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
                    buffer.push(firstPart.replace(/\)\s*$/, ''));
                } else {
                    buffer.push(firstPart);
                    for (i = i + 1; i < lines.length; i++) {
                        var nextRaw = lines[i];
                        var nextLine = nextRaw.trim();
                        if (nextLine === '') {
                            buffer.push('');
                            continue;
                        }
                        if (/\)\s*$/.test(nextLine)) {
                            buffer.push(nextRaw.replace(/\)\s*$/, ''));
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

            var match = line.match(/^([A-Za-z0-9_]+)\s*[:=]\s*(.*)$/);
            if (match) {
                if (applyKeyValue(match[1].toLowerCase(), match[2])) continue;
            }
            textLines.push(rawLine);
        }
        data.text = textLines.join('\n').trim();
        if (data.headerText && !data.headerType) data.headerType = '1';
        if (data.headerMedia && !data.headerType) data.headerType = '2';
        data.headerType = normalizeHeaderType(data.headerType);
        return data;
    }

			    function autofillBuilderFromTarget() {
			        var targetSelect = byId('wa_builder_target');
			        if (!targetSelect) return;
			        var targetId = targetSelect.value || '';
			        applyBuilderOverrideSupport(targetId);
			        toggleBuilderOverrideUi();
			        var globalRaw = getTemplateTextareaValue(targetId);
			        var overrideRaw = getSelectedOverrideValue(targetId);
			        var raw = (overrideRaw !== null) ? overrideRaw : globalRaw;
			        var data = parseWaBlock(raw);
	        if (!data) {
	            data = {
	                mode: (byId('wa_builder_mode') || {}).value || 'buttons',
                text: raw,
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
	        }
	        applyBuilderData(data);
	        toggleMode();
	        updatePreview();
	        refreshTemplatePreviewPanel();
	    }

	    function sendTemplateTest(templateKey, targetId, button, statusId) {
	        if (!templateKey) return;
	        var target = targetId ? byId(targetId) : null;
	        var message = target ? target.value : '';
	        var phone = prompt('Nomor WA tujuan untuk test (contoh: 62812xxxx)', '');
	        if (!phone) return;
	        var csrfInput = document.querySelector('input[name="csrf_token"]');
	        var csrf = csrfInput ? csrfInput.value : '';
	        var statusEl = statusId ? byId(statusId) : byId('wa_test_status_' + templateKey);
	        if (statusEl) {
	            statusEl.textContent = 'Mengirim...';
	            statusEl.className = 'help-block';
	        }
	        var originalText = button ? button.textContent : '';
        if (button) {
            button.disabled = true;
            button.textContent = 'Sending...';
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{Text::url('settings/notifications-test')}', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (button) {
                button.disabled = false;
                button.textContent = originalText || 'Test Send';
            }
            if (xhr.status < 200 || xhr.status >= 300) {
                if (statusEl) {
                    statusEl.textContent = 'Gagal mengirim.';
                    statusEl.className = 'help-block text-danger';
                }
                return;
            }
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) {}
            if (data && data.csrf_token) {
                if (csrfInput) csrfInput.value = data.csrf_token;
            }
            if (!data || !data.ok) {
                if (statusEl) {
                    statusEl.textContent = (data && data.message) ? data.message : 'Gagal mengirim.';
                    statusEl.className = 'help-block text-danger';
                }
                return;
            }
            if (statusEl) {
                statusEl.textContent = data.message ? data.message : 'Test terkirim.';
                statusEl.className = 'help-block text-success';
            }
        };
        var params = 'csrf_token=' + encodeURIComponent(csrf) +
            '&template=' + encodeURIComponent(templateKey) +
            '&phone=' + encodeURIComponent(phone) +
            '&message=' + encodeURIComponent(message || '');
        xhr.send(params);
    }

    function updatePreview() {
        var preview = byId('wa_builder_preview');
        if (!preview) return;
        preview.value = buildBlock();
    }

	    function insertToTarget() {
	        var preview = byId('wa_builder_preview');
	        if (!preview) return;
	        var targetSelect = byId('wa_builder_target');
	        var templateKey = targetSelect ? (targetSelect.value || '') : '';
	        if (!templateKey) return;
	        var block = preview.value || '';

	        if (isBuilderOverrideEnabled()) {
	            if (String(block).trim() === '') {
	                if (!confirm('Isi template kosong. Ini akan menghapus override jika sudah ada. Lanjutkan?')) {
	                    return;
	                }
	            }
		            var csrf = getCsrfToken();
		            var scope = getBuilderOverrideScope();
		            if (scope === 'type') {
		                var typeKey = getBuilderOverrideTypeKey();
		                if (!typeKey) {
		                    alert('Pilih kategori terlebih dahulu.');
		                    return;
		                }
		                postForm(overrideTypePostUrl, {
		                    csrf_token: csrf,
		                    template_key: templateKey,
		                    type_key: typeKey,
		                    message: block
		                });
		                return;
		            }
		            if (scope === 'purpose') {
		                var purposeKey = getBuilderOverridePurposeKey();
		                if (!purposeKey) {
		                    alert('Pilih purpose terlebih dahulu.');
		                    return;
		                }
		                postForm(overridePurposePostUrl, {
		                    csrf_token: csrf,
		                    template_key: templateKey,
		                    purpose_key: purposeKey,
		                    message: block
		                });
		                return;
		            }

		            var planId = getBuilderOverridePlanId();
		            if (!planId) {
		                alert('Pilih plan terlebih dahulu.');
		                return;
	            }
	            postForm(overridePlanPostUrl, {
	                csrf_token: csrf,
	                template_key: templateKey,
	                plan_id: planId,
	                message: block
	            });
	            return;
	        }

	        var target = byId(templateKey);
	        if (!target) return;
	        target.value = block;
	        target.focus();
	        refreshTemplatePreviewPanel();
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
		    if (byId('wa_builder_upload_media')) byId('wa_builder_upload_media').addEventListener('click', function () {
		        uploadHeaderMedia();
		    });
		    if (byId('wa_builder_insert')) byId('wa_builder_insert').addEventListener('click', insertToTarget);
		    if (byId('wa_builder_test_send')) byId('wa_builder_test_send').addEventListener('click', function () {
		        var targetSelect = byId('wa_builder_target');
		        var templateKey = targetSelect ? (targetSelect.value || '') : '';
		        if (!templateKey) {
		            alert('Select target template first.');
		            return;
		        }
		        sendTemplateTest(templateKey, 'wa_builder_preview', this, 'wa_builder_test_status');
		    });
		        if (byId('wa_builder_target')) byId('wa_builder_target').addEventListener('change', function () {
		            autofillBuilderFromTarget();
		        });
	        if (byId('wa_builder_override_enabled')) byId('wa_builder_override_enabled').addEventListener('change', function () {
	            toggleBuilderOverrideUi();
	            autofillBuilderFromTarget();
	        });
	        if (byId('wa_builder_override_scope')) byId('wa_builder_override_scope').addEventListener('change', function () {
	            toggleBuilderOverrideUi();
	            autofillBuilderFromTarget();
	        });
	        if (byId('wa_builder_override_type')) byId('wa_builder_override_type').addEventListener('change', function () {
	            autofillBuilderFromTarget();
	        });
		        if (byId('wa_builder_override_plan')) byId('wa_builder_override_plan').addEventListener('change', function () {
		            autofillBuilderFromTarget();
		        });
		        if (byId('wa_builder_override_purpose')) byId('wa_builder_override_purpose').addEventListener('change', function () {
		            autofillBuilderFromTarget();
		        });

		        if (byId('wa_template_preview_mode')) byId('wa_template_preview_mode').addEventListener('change', refreshTemplatePreviewPanel);
		        if (byId('wa_template_preview_target')) byId('wa_template_preview_target').addEventListener('change', refreshTemplatePreviewPanel);
		        if (byId('wa_template_preview_type')) byId('wa_template_preview_type').addEventListener('change', refreshTemplatePreviewPanel);
		        if (byId('wa_template_preview_plan')) byId('wa_template_preview_plan').addEventListener('change', refreshTemplatePreviewPanel);
		        if (byId('wa_template_preview_purpose')) byId('wa_template_preview_purpose').addEventListener('change', refreshTemplatePreviewPanel);
		        if (byId('wa_template_preview_delete')) byId('wa_template_preview_delete').addEventListener('click', function () {
		            deletePreviewOverride();
		        });
		        if (byId('wa_template_preview_edit')) byId('wa_template_preview_edit').addEventListener('click', function () {
		            editPreviewOverrideInBuilder();
		        });
		        if (byId('wa_builder_mode')) byId('wa_builder_mode').addEventListener('change', function () {
		            toggleMode();
		            toggleHeaderFields();
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
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.wa-template-test');
            if (!btn) return;
            var templateKey = btn.getAttribute('data-template') || '';
            var targetId = btn.getAttribute('data-target') || '';
            sendTemplateTest(templateKey, targetId, btn);
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
		        applyBuilderOverrideSupport((byId('wa_builder_target') || {}).value || '');
		        toggleBuilderOverrideUi();
		        autofillBuilderFromTarget();
		    }

    document.addEventListener('DOMContentLoaded', initBuilder);
})();
</script>

{include file="sections/footer.tpl"}
