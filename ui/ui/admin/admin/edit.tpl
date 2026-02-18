{include file="sections/header.tpl"}
<!-- user-edit -->

<form class="form-horizontal" method="post" enctype="multipart/form-data" role="form"
    action="{Text::url('settings/users-edit-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" id="ctx_actor_role" value="{$_admin['user_type']}">
    <input type="hidden" id="ctx_self_admin_id" value="{$self_admin_id}">
    <input type="hidden" id="ctx_target_id" value="{$d['id']}">
    <input type="hidden" id="ctx_current_admin_id" value="{$_admin['id']}">
    <div class="row">
        <div class="col-sm-6 col-md-6">
            <div
                class="panel panel-{if $d['status'] != 'Active'}danger{else}primary{/if} panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Profile')}</div>
                <div class="panel-body">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <center>
                        {assign var='adminPhotoPath' value=$d['photo']}
                        {assign var='adminPhotoFallback' value=$app_url|cat:'/'|cat:$UPLOAD_PATH|cat:'/admin.default.png'}
                        {if !$adminPhotoPath || strstr($adminPhotoPath, 'default')}
                            {assign var='adminPhotoSrc' value=$adminPhotoFallback}
                        {else}
                            {assign var='cleanAdminPhoto' value=$adminPhotoPath|trim:'/'}
                            {assign var='adminPhotoSrc' value=$app_url|cat:'/'|cat:$UPLOAD_PATH|cat:'/'|cat:$cleanAdminPhoto|cat:'.thumb.jpg'}
                        {/if}
                        <img src="{$adminPhotoSrc}" width="200"
                            onerror="this.src='{$adminPhotoFallback}'" class="img-circle img-responsive" alt="Foto"
                            onclick="return deletePhoto({$d['id']})">
                    </center><br>
                    <div class="form-group">
                        <label class="col-md-3 col-xs-12 control-label">{Lang::T('Photo')}</label>
                        <div class="col-md-6 col-xs-8">
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                        <div class="form-group col-md-3 col-xs-4" title="Not always Working">
                            <label class=""><input type="checkbox" checked name="faceDetect" value="yes"> Facedetect</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Full Name')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="fullname" name="fullname"
                                value="{$d['fullname']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Phone')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="phone" name="phone" value="{$d['phone']}" inputmode="tel" autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Email')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="email" name="email" value="{$d['email']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="city" name="city"
                                placeholder="{Lang::T('City')}" value="{$d['city']}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="subdistrict" name="subdistrict"
                                placeholder="{Lang::T('Sub District')}" value="{$d['subdistrict']}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="ward" name="ward"
                                placeholder="{Lang::T('Ward')}" value="{$d['ward']}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-6">
            <div
                class="panel panel-{if $d['status'] != 'Active'}danger{else}primary{/if} panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Credentials')}</div>
                <div class="panel-body">
                    {if ($_admin['id']) neq ($d['id'])}
                        <input type="hidden" name="root" id="root_resolved" value="{$d['root']}">
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Status')}</label>
                            <div class="col-md-9">
                                <select name="status" id="status" class="form-control">
                                    <option value="Active" {if $d['status'] eq 'Active'}selected="selected" {/if}>
                                        {Lang::T('Active')}</option>
                                    <option value="Inactive" {if $d['status'] eq 'Inactive'}selected="selected" {/if}>
                                        {Lang::T('Inactive')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('User Type')}</label>
                            <div class="col-md-9">
                                <select name="user_type" id="user_type" class="form-control" onchange="checkUserType(this)">
                                    {if $_admin['user_type'] eq 'Agent'}
                                        <option value="Sales" {if $d['user_type'] eq 'Sales'}selected="selected" {/if}>Sales
                                        </option>
                                    {/if}
                                    {if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'SuperAdmin'}
                                        <option value="Report" {if $d['user_type'] eq 'Report'}selected="selected" {/if}>Report
                                            Viewer</option>
                                        <option value="Agent" {if $d['user_type'] eq 'Agent'}selected="selected" {/if}>Agent
                                        </option>
                                    {/if}
                                    {if $_admin['user_type'] eq 'SuperAdmin'}
                                        <option value="Sales" {if $d['user_type'] eq 'Sales'}selected="selected" {/if}>Sales
                                        </option>
                                        <option value="Admin" {if $d['user_type'] eq 'Admin'}selected="selected" {/if}>
                                            Administrator</option>
                                        <option value="SuperAdmin" {if $d['user_type'] eq 'SuperAdmin'}selected="selected"
                                            {/if}>Super Administrator</option>
                                    {/if}
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $_admin['user_type'] neq 'SuperAdmin' || $d['user_type'] neq 'Admin'}hidden{/if}" id="superAdminChooser">
                            <label class="col-md-3 control-label">{Lang::T('Parent SuperAdmin')}</label>
                            <div class="col-md-9">
                                <select id="root_superadmin" class="form-control">
                                    <option value="">{Lang::T('Select SuperAdmin')}</option>
                                    {foreach $superadmins_parent as $sup}
                                        <option value="{$sup['id']}" {if $d['root'] == $sup['id']}selected{/if}>
                                            {$sup['username']} | {$sup['fullname']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $_admin['user_type'] neq 'SuperAdmin' || ($d['user_type'] neq 'Agent' && $d['user_type'] neq 'Report')}hidden{/if}" id="adminChooser">
                            <label class="col-md-3 control-label">{Lang::T('Parent Admin')}</label>
                            <div class="col-md-9">
                                <select id="root_admin" class="form-control">
                                    <option value="">{Lang::T('Select Admin')}</option>
                                    {foreach $admins_parent as $adm}
                                        <option value="{$adm['id']}" {if $d['root'] == $adm['id']}selected{/if}>
                                            {$adm['username']} | {$adm['fullname']}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $d['user_type'] neq 'Sales'}hidden{/if}" id="agentChooser">
                            <label class="col-md-3 control-label">{Lang::T('Parent Agent')}</label>
                            <div class="col-md-9">
                                <select id="root_agent" class="form-control">
                                    <option value="">{Lang::T('Select Agent')}</option>
                                    {foreach $agents as $agent}
                                        <option value="{$agent['id']}" {if $d['root'] == $agent['id']}selected{/if}>
                                            {$agent['username']} | {$agent['fullname']} |
                                            {$agent['phone']}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $d['user_type'] eq 'SuperAdmin'}hidden{/if}" id="routerModeWrap">
                            <label class="col-md-3 control-label">{Lang::T('Router Access')}</label>
                            <div class="col-md-9">
                                <select name="router_access_mode" id="router_access_mode" class="form-control">
                                    <option value="all" {if $router_assignment_mode eq 'all'}selected{/if}>{Lang::T('All')}</option>
                                    <option value="list" {if $router_assignment_mode eq 'list'}selected{/if}>{Lang::T('List')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $d['user_type'] eq 'SuperAdmin' || $router_assignment_mode neq 'list'}hidden{/if}" id="routerListWrap">
                            <label class="col-md-3 control-label">{Lang::T('Allowed Routers')}</label>
                            <div class="col-md-9">
                                <select name="router_access_ids[]" id="router_access_ids" class="form-control select2" multiple>
                                    {foreach $assignable_routers as $router}
                                        <option value="{$router['id']}" {if in_array($router['id'], $router_assignment_ids)}selected{/if}>{$router['name']}</option>
                                    {/foreach}
                                </select>
                                <span class="help-block">{Lang::T('Used when Router Access = List')}</span>
                            </div>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="username" name="username"
                                value="{$d['username']}" autocomplete="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                            <span class="help-block">{Lang::T('Keep Blank to do not change Password')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="cpassword" name="cpassword"
                                placeholder="{Lang::T('Confirm Password')}" autocomplete="new-password">
                            <span class="help-block">{Lang::T('Keep Blank to do not change Password')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Admin API Key')}</label>
                        <div class="col-md-9">
                            <div class="input-group js-api-key-hover">
                                <input type="password" class="form-control" id="admin_api_key" name="admin_api_key"
                                    autocomplete="new-password" placeholder="{Lang::T('Fill to change')}">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" id="admin_api_key_generate" title="{Lang::T('Generate/Rotate')}">
                                        <i class="fa fa-refresh" aria-hidden="true"></i>
                                        <span class="sr-only">{Lang::T('Generate/Rotate')}</span>
                                    </button>
                                </span>
                            </div>
                            {if $admin_api_key_set}
                                <span class="help-block">{Lang::T('API key is stored. Leave blank to keep it.')}</span>
                            {else}
                                <span class="help-block">{Lang::T('Leave blank if not used.')}</span>
                            {/if}
                            <span class="help-block">{Lang::T('Key will be hashed after saving and cannot be shown again.')}</span>
                            <span class="help-block">{Lang::T('Used for all admin endpoints (API).')}</span>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="admin_api_key_clear" value="1"> {Lang::T('Remove API Key')}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group text-center">
        <button class="btn btn-primary" onclick="return ask(this, 'Continue the Admin change process?')"
            type="submit">{Lang::T('Save Changes')}</button>
        Or <a href="{Text::url('settings/users')}">{Lang::T('Cancel')}</a>
    </div>
</form>

<script>
    (function () {
        function byId(id) {
            return document.getElementById(id);
        }

        function getValue(id) {
            var el = byId(id);
            return el ? String(el.value || '').trim() : '';
        }

        function setHidden(id, hidden) {
            var el = byId(id);
            if (!el) {
                return;
            }
            if (hidden) {
                el.classList.add('hidden');
            } else {
                el.classList.remove('hidden');
            }
        }

        function clearRouterSelection() {
            var input = byId('router_access_ids');
            if (!input) {
                return;
            }
            for (var i = 0; i < input.options.length; i++) {
                input.options[i].selected = false;
            }
            if (window.jQuery) {
                window.jQuery(input).trigger('change');
            }
        }

        function refreshRouterSelect2() {
            var input = byId('router_access_ids');
            if (!input || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }
            var $input = window.jQuery(input);
            if ($input.hasClass('select2-hidden-accessible')) {
                var container = input.nextElementSibling;
                if (container && container.classList.contains('select2-container')) {
                    container.style.width = '100%';
                }
                $input.trigger('change.select2');
                return;
            }
            $input.select2({ theme: 'bootstrap', width: '100%' });
        }

        function applyRootByRole(role) {
            var actorRole = getValue('ctx_actor_role');
            var selfAdminId = getValue('ctx_self_admin_id');
            var targetId = getValue('ctx_target_id');
            var currentAdminId = getValue('ctx_current_admin_id');
            if (currentAdminId === targetId) {
                return;
            }

            var rootValue = '';
            if (role === 'Admin') {
                if (actorRole === 'SuperAdmin') {
                    rootValue = getValue('root_superadmin') || selfAdminId;
                }
            } else if (role === 'Sales') {
                if (actorRole === 'Agent') {
                    rootValue = selfAdminId;
                } else {
                    rootValue = getValue('root_agent');
                }
            } else if (role === 'Agent' || role === 'Report') {
                if (actorRole === 'Admin') {
                    rootValue = selfAdminId;
                } else {
                    rootValue = getValue('root_admin');
                }
            }

            var rootResolved = byId('root_resolved');
            if (rootResolved) {
                rootResolved.value = rootValue;
            }
        }

        function toggleRouterList() {
            var mode = byId('router_access_mode');
            var modeWrap = byId('routerModeWrap');
            if (!mode || !modeWrap || modeWrap.classList.contains('hidden')) {
                setHidden('routerListWrap', true);
                return;
            }
            if (mode.value === 'list') {
                setHidden('routerListWrap', false);
                setTimeout(refreshRouterSelect2, 0);
            } else {
                setHidden('routerListWrap', true);
            }
        }

        function checkUserType(field) {
            if (!field) {
                return;
            }
            var role = field.value;
            var actorRole = getValue('ctx_actor_role');
            var targetId = getValue('ctx_target_id');
            var currentAdminId = getValue('ctx_current_admin_id');
            if (currentAdminId === targetId) {
                return;
            }

            setHidden('superAdminChooser', true);
            setHidden('adminChooser', true);
            setHidden('agentChooser', true);

            if (role === 'Admin' && actorRole === 'SuperAdmin') {
                setHidden('superAdminChooser', false);
            }
            if ((role === 'Agent' || role === 'Report') && actorRole === 'SuperAdmin') {
                setHidden('adminChooser', false);
            }
            if (role === 'Sales' && actorRole !== 'Agent') {
                setHidden('agentChooser', false);
            }

            var modeSelect = byId('router_access_mode');
            if (role === 'SuperAdmin') {
                setHidden('routerModeWrap', true);
                setHidden('routerListWrap', true);
                if (modeSelect) {
                    modeSelect.value = 'all';
                }
                clearRouterSelection();
            } else {
                setHidden('routerModeWrap', false);
                toggleRouterList();
            }

            applyRootByRole(role);
        }

        function deletePhoto(id) {
            if (confirm('Delete photo?') && confirm('Are you sure to delete photo?')) {
                window.location.href = '{Text::url('settings/users-edit/')}'+id+'/deletePhoto';
            }
        }

        function generateAdminApiKey(length) {
            length = length || 40;
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var result = '';
            if (window.crypto && window.crypto.getRandomValues) {
                var array = new Uint8Array(length);
                window.crypto.getRandomValues(array);
                for (var i = 0; i < array.length; i++) {
                    result += chars.charAt(array[i] % chars.length);
                }
                return result;
            }
            for (var j = 0; j < length; j++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        window.checkUserType = checkUserType;
        window.deletePhoto = deletePhoto;

        document.addEventListener('DOMContentLoaded', function () {
            var userType = byId('user_type');
            var mode = byId('router_access_mode');

            if (mode) {
                mode.addEventListener('change', toggleRouterList);
            }

            ['root_superadmin', 'root_admin', 'root_agent'].forEach(function (id) {
                var el = byId(id);
                if (el) {
                    el.addEventListener('change', function () {
                        checkUserType(userType);
                    });
                }
            });

            checkUserType(userType);
            toggleRouterList();
            setTimeout(refreshRouterSelect2, 0);

            var form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function () {
                    checkUserType(userType);
                });
            }

            var button = byId('admin_api_key_generate');
            var input = byId('admin_api_key');
            if (button && input) {
                button.addEventListener('click', function () {
                    input.value = generateAdminApiKey(40);
                    var clearBox = document.querySelector('input[name="admin_api_key_clear"]');
                    if (clearBox) {
                        clearBox.checked = false;
                    }
                    input.focus();
                    input.select();
                });
            }

            var group = document.querySelector('.js-api-key-hover');
            if (group && input) {
                var originalType = input.type;
                group.addEventListener('mouseenter', function () {
                    input.type = 'text';
                });
                group.addEventListener('mouseleave', function () {
                    input.type = originalType || 'password';
                });
            }
        });

        window.addEventListener('load', function () {
            refreshRouterSelect2();
            toggleRouterList();
        });
    })();
</script>
{include file="sections/footer.tpl"}
