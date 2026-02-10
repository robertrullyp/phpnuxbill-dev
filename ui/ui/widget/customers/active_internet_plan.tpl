{if $_bills}
    <div class="box box-primary box-solid">
        {foreach $_bills as $_bill}
            {if $_bill['routers'] != 'radius'}
                <div class="box-header">
                    <h3 class="box-title">{$_bill['routers']}</h3>
                    <div class="btn-group pull-right">
                        {if $_bill['type'] == 'Hotspot'}
                            {if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}
                        {else if $_bill['type'] == 'PPPOE'}
                            {if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}
                        {else if $_bill['type'] == 'VPN'}
                            {if $_c['pppoe_plan']==''}VPN Plan{else}{$_c['vpn_plan']}{/if}
                        {/if}
                    </div>
                </div>
            {else}
                <div class="box-header">
                    <h3 class="box-title">{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</h3>
                </div>
            {/if}
            <div style="margin-left: 5px; margin-right: 5px;">
                <table class="table table-bordered table-striped table-bordered table-hover" style="margin-bottom: 0px;">
                    <tr>
                        <td class="small text-primary text-uppercase text-normal">{Lang::T('Package Name')}</td>
                        <td class="small mb15">
                            {$_bill['namebp']}
                            {if $_bill['status'] != 'on'}
                                <a class="label label-danger pull-right"
                                    href="{Text::url('order/package')}">{Lang::T('Expired')}</a>
                            {/if}
                        </td>
                    </tr>
                    {if $_c['show_bandwidth_plan'] == 'yes'}
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">{Lang::T('Bandwidth')}</td>
                            <td class="small mb15">
                                {$_bill['name_bw']}
                            </td>
                        </tr>
                    {/if}
                    <tr>
                        <td class="small text-info text-uppercase text-normal">{Lang::T('Created On')}</td>
                        <td class="small mb15">
                            {if $_bill['time'] ne ''}
                                {Lang::dateAndTimeFormat($_bill['recharged_on'],$_bill['recharged_time'])}
                            {/if}
                            &nbsp;</td>
                    </tr>
                    <tr>
                        <td class="small text-danger text-uppercase text-normal">{Lang::T('Expires On')}</td>
                        <td class="small mb15 text-danger">
                            {if $_bill['time'] ne ''}
                                {Lang::dateAndTimeFormat($_bill['expiration'],$_bill['time'])}
                            {/if}&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="small text-success text-uppercase text-normal">{Lang::T('Type')}</td>
                        <td class="small mb15 text-success">
                            <b>{if $_bill['prepaid'] eq yes}Prepaid{else}Postpaid{/if}</b>
                            {$_bill['plan_type']}
                        </td>
                    </tr>
                    {if $_bill['type'] == 'VPN' && $_bill['routers'] == $vpn['routers']}
                        <tr>
                            <td class="small text-success text-uppercase text-normal">{Lang::T('Public IP')}</td>
                            <td class="small mb15">{$vpn['public_ip']} / {$vpn['port_name']}</td>
                        </tr>
                        <tr>
                            <td class="small text-success text-uppercase text-normal">{Lang::T('Private IP')}</td>
                            <td class="small mb15">{$_user['pppoe_ip']}</td>
                        </tr>
                        {foreach $cf as $tcf}
                            <tr>
                                {if $tcf['field_name'] == 'Winbox' or $tcf['field_name'] == 'Api' or $tcf['field_name'] == 'Web'}
                                    <td class="small text-info text-uppercase text-normal">{$tcf['field_name']} - Port</td>
                                    <td class="small mb15"><a href="http://{$vpn['public_ip']}:{$tcf['field_value']}"
                                            target="_blank">{$tcf['field_value']}</a></td>
                                </tr>
                            {/if}
                        {/foreach}
                    {/if}

                    {if $nux_ip neq ''}
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">{Lang::T('Current IP')}</td>
                            <td class="small mb15">{$nux_ip}</td>
                        </tr>
                    {/if}
                    {if $nux_mac neq ''}
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">{Lang::T('Current MAC')}</td>
                            <td class="small mb15">{$nux_mac}</td>
                        </tr>
                    {/if}
                    {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on' && $_bill['routers'] != 'radius' && $_c['hs_auth_method'] != 'hchap'}
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">{Lang::T('Login Status')}</td>
                            <td class="small mb15" id="login_status_{$_bill['id']}">
                                <img src="{$app_url}/ui/ui/images/loading.gif">
                            </td>
                        </tr>
                    {/if}
                    {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on' && $_c['hs_auth_method'] == 'hchap'}
                        <tr>
                            <td class="small text-primary text-uppercase text-normal">{Lang::T('Login Status')}</td>
                            <td class="small mb15">
                                {if $logged == '1'}
                                    <a href="http://{$hostname}/status" class="btn btn-success btn-xs btn-block">
                                        {Lang::T('You are Online, Check Status')}</a>
                                {else}
                                    <a href="{Text::url('home&mikrotik=login')}"
                                        onclick="return ask(this, '{Lang::T('Connect to Internet')}')"
                                        class="btn btn-danger btn-xs btn-block">{Lang::T('Not Online, Login now?')}</a>
                                {/if}
                            </td>
                        </tr>
                    {/if}
                    {if $_bill['genieacs_eligible'] && $genieacs['enabled']}
                        {if $genieacs['can_manage']}
                            <tr>
                                <td class="small text-primary text-uppercase text-normal">WiFi SSID</td>
                                <td class="small mb15">
                                    <span id="wifi_ssid_text_{$_bill['id']}">
                                        {if $genieacs['ssid'] neq ''}{$genieacs['ssid']|escape}{else}-{/if}
                                    </span>
                                    <div id="wifi_ssid_edit_wrap_{$_bill['id']}" style="display: none; margin-top: 6px;">
                                        <input type="text" class="form-control input-sm" id="wifi_ssid_input_{$_bill['id']}"
                                            maxlength="64" value="{$genieacs['ssid']|escape}" placeholder="SSID">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="small text-primary text-uppercase text-normal">WiFi Password</td>
                                <td class="small mb15">
                                    <span id="wifi_password_text_{$_bill['id']}">
                                        {if $genieacs['password'] neq ''}{$genieacs['password']|escape}{else}-{/if}
                                    </span>
                                    <div id="wifi_password_edit_wrap_{$_bill['id']}" style="display: none; margin-top: 6px;">
                                        <input type="text" class="form-control input-sm"
                                            id="wifi_password_input_{$_bill['id']}" minlength="8" maxlength="63"
                                            value="{$genieacs['password']|escape}" placeholder="WiFi Password">
                                    </div>
                                </td>
                            </tr>
                            {if $genieacs['error'] neq ''}
                                <tr>
                                    <td class="small text-warning text-uppercase text-normal">GenieACS</td>
                                    <td class="small mb15 text-warning">{$genieacs['error']|escape}</td>
                                </tr>
                            {/if}
                        {else}
                            <tr>
                                <td class="small text-warning text-uppercase text-normal">GenieACS</td>
                                <td class="small mb15 text-warning">{Lang::T('Device not assigned. Contact admin.')}</td>
                            </tr>
                        {/if}
                    {/if}
                    <tr>
                        <td class="small text-primary text-uppercase text-normal">
                            {if $_bill['status'] == 'on' && $_bill['prepaid'] != 'YES'}
                                <a href="{Text::url('home&deactivate=', $_bill['id'])}"
                                    onclick="return ask(this, '{Lang::T('Deactivate')}?')" class="btn btn-danger btn-xs"><i
                                        class="glyphicon glyphicon-trash"></i></a>
                            {/if}
                        </td>
                        <td class="small row">
                            {if $_bill['status'] != 'on' && $_bill['prepaid'] != 'yes' && $_c['extend_expired']}
                                <a class="btn btn-warning text-black btn-sm"
                                    href="{Text::url('home&extend=', $_bill['id'], '&stoken=', App::getToken())}"
                                    onclick="return ask(this, '{Text::toHex($_c['extend_confirmation'])}')">{Lang::T('Extend')}</a>
                            {/if}
                            <div class="btn-group pull-right" role="group" aria-label="Plan actions">
                                <a class="btn btn-primary btn-sm"
                                    href="{Text::url('home&recharge=', $_bill['id'], '&stoken=', App::getToken())}"
                                    onclick="return ask(this, '{Lang::T('Recharge')}?')">{Lang::T('Recharge')}</a>
                                {if $_bill['genieacs_eligible'] && $genieacs['enabled'] && $genieacs['can_manage']}
                                    <button type="button" class="btn btn-info btn-sm"
                                        id="wifi_edit_btn_{$_bill['id']}"
                                        data-label-edit="Edit WiFi"
                                        data-label-save="{Lang::T('Save Changes')}"
                                        onclick="return handleWifiEditAction('{$_bill['id']}');">
                                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> Edit WiFi
                                    </button>
                                    <button type="button" class="btn btn-default btn-sm"
                                        id="wifi_cancel_btn_{$_bill['id']}" style="display: none;"
                                        onclick="return cancelWifiEdit('{$_bill['id']}');">
                                        {Lang::T('Cancel')}
                                    </button>
                                    <form method="post" action="{Text::url('home')}"
                                        id="wifi_update_form_{$_bill['id']}" style="display: inline-block;">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token|escape}">
                                        <input type="hidden" name="send" value="genieacs_wifi_update">
                                        <input type="hidden" name="device_id" value="{$genieacs['device_id']|escape}">
                                        <input type="hidden" name="ssid_path" value="{$genieacs['ssid_path']|escape}">
                                        <input type="hidden" name="password_path" value="{$genieacs['password_path']|escape}">
                                        <input type="hidden" name="wifi_ssid" id="wifi_ssid_hidden_{$_bill['id']}"
                                            value="{$genieacs['ssid']|escape}">
                                        <input type="hidden" name="wifi_password" id="wifi_password_hidden_{$_bill['id']}"
                                            value="{$genieacs['password']|escape}">
                                    </form>
                                    <form method="post" action="{Text::url('home')}"
                                        id="genieacs_reboot_form_{$_bill['id']}" style="display: inline-block;"
                                        onsubmit="return confirmGenieacsReboot('{$_bill['id']}');">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token|escape}">
                                        <input type="hidden" name="send" value="genieacs_reboot_device">
                                        <input type="hidden" name="device_id" value="{$genieacs['device_id']|escape}">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            id="wifi_reboot_btn_{$_bill['id']}"
                                            data-confirm-text="{Lang::T('Restart/Reboot device now?')}"
                                            data-label-default="Restart"
                                            data-label-loading="{Lang::T('Rebooting...')}">
                                            <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span> Restart
                                        </button>
                                    </form>
                                {/if}
                                <a class="btn btn-warning text-black btn-sm"
                                    href="{Text::url('home&sync=', $_bill['id'], '&stoken=', App::getToken())}"
                                    onclick="return ask(this, '{Lang::T('Sync account if you failed login to internet')}?')"
                                    data-toggle="tooltip" data-placement="top"
                                    title="{Lang::T('Sync account if you failed login to internet')}"><span
                                        class="glyphicon glyphicon-refresh" aria-hidden="true"></span> {Lang::T('Sync')}</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            &nbsp;&nbsp;
        {/foreach}
    </div>
    {foreach $_bills as $_bill}
        {if $_bill['type'] == 'Hotspot' && $_bill['status'] == 'on' && $_c['hs_auth_method'] != 'hchap'}
            <script>
                setTimeout(() => {
                    $.ajax({
                        url: "{Text::url('autoload_user/isLogin/')}{$_bill['id']}",
                        cache: false,
                        success: function(msg) {
                            $("#login_status_{$_bill['id']}").html(msg);
                        }
                    });
                }, 2000);
            </script>
        {/if}
    {/foreach}
    <script>
        function setWifiEditButtonState(billId, isEditing) {
            var editButton = document.getElementById('wifi_edit_btn_' + billId);
            var cancelButton = document.getElementById('wifi_cancel_btn_' + billId);
            if (!editButton) {
                return;
            }
            var editLabel = editButton.getAttribute('data-label-edit') || 'Edit WiFi';
            var saveLabel = editButton.getAttribute('data-label-save') || 'Save';
            if (isEditing) {
                editButton.className = 'btn btn-success btn-sm';
                editButton.innerHTML = '<span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> ' + saveLabel;
                if (cancelButton) {
                    cancelButton.style.display = 'inline-block';
                }
            } else {
                editButton.className = 'btn btn-info btn-sm';
                editButton.innerHTML = '<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> ' + editLabel;
                if (cancelButton) {
                    cancelButton.style.display = 'none';
                }
            }
        }

        function toggleWifiEdit(billId) {
            var ssidWrap = document.getElementById('wifi_ssid_edit_wrap_' + billId);
            var passwordWrap = document.getElementById('wifi_password_edit_wrap_' + billId);
            if (!ssidWrap || !passwordWrap) {
                return false;
            }
            var isOpen = ssidWrap.style.display !== 'none';
            ssidWrap.style.display = isOpen ? 'none' : 'block';
            passwordWrap.style.display = isOpen ? 'none' : 'block';
            setWifiEditButtonState(billId, !isOpen);
            return false;
        }

        function handleWifiEditAction(billId) {
            var ssidWrap = document.getElementById('wifi_ssid_edit_wrap_' + billId);
            if (!ssidWrap) {
                return false;
            }
            var isOpen = ssidWrap.style.display !== 'none';
            if (!isOpen) {
                return toggleWifiEdit(billId);
            }
            return submitWifiUpdate(billId);
        }

        function closeWifiEdit(billId) {
            var ssidWrap = document.getElementById('wifi_ssid_edit_wrap_' + billId);
            var passwordWrap = document.getElementById('wifi_password_edit_wrap_' + billId);
            if (!ssidWrap || !passwordWrap) {
                return false;
            }
            ssidWrap.style.display = 'none';
            passwordWrap.style.display = 'none';
            setWifiEditButtonState(billId, false);
            return false;
        }

        function cancelWifiEdit(billId) {
            var ssidInput = document.getElementById('wifi_ssid_input_' + billId);
            var passwordInput = document.getElementById('wifi_password_input_' + billId);
            var ssidHidden = document.getElementById('wifi_ssid_hidden_' + billId);
            var passwordHidden = document.getElementById('wifi_password_hidden_' + billId);
            if (ssidInput && ssidHidden) {
                ssidInput.value = ssidHidden.value || '';
            }
            if (passwordInput && passwordHidden) {
                passwordInput.value = passwordHidden.value || '';
            }
            return closeWifiEdit(billId);
        }

        function submitWifiUpdate(billId) {
            var ssidInput = document.getElementById('wifi_ssid_input_' + billId);
            var passwordInput = document.getElementById('wifi_password_input_' + billId);
            var ssidHidden = document.getElementById('wifi_ssid_hidden_' + billId);
            var passwordHidden = document.getElementById('wifi_password_hidden_' + billId);
            var form = document.getElementById('wifi_update_form_' + billId);
            if (!ssidInput || !passwordInput || !ssidHidden || !passwordHidden || !form) {
                return false;
            }

            var ssid = (ssidInput.value || '').trim();
            var password = passwordInput.value || '';
            if (ssid.length < 1 || ssid.length > 64) {
                alert('SSID must be between 1 and 64 characters');
                ssidInput.focus();
                return false;
            }
            if (password.length < 8 || password.length > 63) {
                alert('WiFi password must be between 8 and 63 characters');
                passwordInput.focus();
                return false;
            }

            ssidHidden.value = ssid;
            passwordHidden.value = password;
            closeWifiEdit(billId);
            form.submit();
            return false;
        }

        function confirmGenieacsReboot(billId) {
            var rebootButton = document.getElementById('wifi_reboot_btn_' + billId);
            var confirmText = 'Restart/Reboot device now?';
            if (rebootButton) {
                confirmText = rebootButton.getAttribute('data-confirm-text') || confirmText;
            }

            if (!window.confirm(confirmText)) {
                return false;
            }

            if (rebootButton) {
                var loadingLabel = rebootButton.getAttribute('data-label-loading') || 'Rebooting...';
                rebootButton.setAttribute('disabled', 'disabled');
                rebootButton.innerHTML = '<span class="glyphicon glyphicon-time" aria-hidden="true"></span> ' + loadingLabel;
            }
            return true;
        }
    </script>
{/if}
