{include file="sections/header.tpl"}
<!-- user-edit -->

<form class="form-horizontal" method="post" role="form" action="{Text::url('settings/users-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="root" id="root_resolved" value="">
    <input type="hidden" id="ctx_actor_role" value="{$_admin['user_type']}">
    <input type="hidden" id="ctx_self_admin_id" value="{$self_admin_id}">
    <div class="row">
        <div class="col-sm-6 col-md-6">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Profile')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Full Name')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="fullname" name="fullname">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Phone')}</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Email')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="city" name="city" placeholder="{Lang::T('City')}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="subdistrict" name="subdistrict" placeholder="{Lang::T('Sub District')}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="ward" name="ward" placeholder="{Lang::T('Ward')}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-6">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Credentials')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('User Type')}</label>
                        <div class="col-md-9">
                            <select name="user_type" id="user_type" class="form-control" onchange="checkUserType(this)">
                                {if $_admin['user_type'] eq 'Agent'}
                                    <option value="Sales">{Lang::T('Sales')}</option>
                                {/if}
                                {if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'SuperAdmin'}
                                    <option value="Report">{Lang::T('Report Viewer')}</option>
                                    <option value="Agent">{Lang::T('Agent')}</option>
                                {/if}
                                {if $_admin['user_type'] eq 'SuperAdmin'}
                                    <option value="Sales">{Lang::T('Sales')}</option>
                                    <option value="Admin">{Lang::T('Administrator')}</option>
                                    <option value="SuperAdmin">{Lang::T('Super Administrator')}</option>
                                {/if}
                            </select>
                        </div>
                    </div>
                    <div class="form-group hidden" id="superAdminChooser">
                        <label class="col-md-3 control-label">{Lang::T('Parent SuperAdmin')}</label>
                        <div class="col-md-9">
                            <select id="root_superadmin" class="form-control">
                                <option value="">{Lang::T('Select SuperAdmin')}</option>
                                {foreach $superadmins_parent as $sup}
                                    <option value="{$sup['id']}" {if $sup['id'] == $self_admin_id}selected{/if}>
                                        {$sup['username']} | {$sup['fullname']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group hidden" id="adminChooser">
                        <label class="col-md-3 control-label">{Lang::T('Parent Admin')}</label>
                        <div class="col-md-9">
                            <select id="root_admin" class="form-control">
                                <option value="">{Lang::T('Select Admin')}</option>
                                {foreach $admins_parent as $adm}
                                    <option value="{$adm['id']}">{$adm['username']} | {$adm['fullname']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group hidden" id="agentChooser">
                        <label class="col-md-3 control-label">{Lang::T('Parent Agent')}</label>
                        <div class="col-md-9">
                            <select id="root_agent" class="form-control">
                                <option value="">{Lang::T('Select Agent')}</option>
                                {foreach $agents as $agent}
                                    <option value="{$agent['id']}">{$agent['username']} | {$agent['fullname']} | {$agent['phone']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="username" name="username" autocomplete="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="password" value="{rand(000000,999999)}" name="password"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-5 control-label">{Lang::T('Send Notification')}</label>
                        <div class="col-md-7">
                            <select name="send_notif" id="send_notif" class="form-control">
                                <option value="-">{Lang::T("Don't Send")}</option>
                                <option value="sms">{Lang::T('By SMS')}</option>
                                <option value="wa">{Lang::T('By WhatsApp')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="routerModeWrap">
                        <label class="col-md-5 control-label">{Lang::T('Router Access')}</label>
                        <div class="col-md-7">
                            <select name="router_access_mode" id="router_access_mode" class="form-control">
                                <option value="all">{Lang::T('All')}</option>
                                <option value="list">{Lang::T('List')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group hidden" id="routerListWrap">
                        <label class="col-md-3 control-label">{Lang::T('Allowed Routers')}</label>
                        <div class="col-md-9">
                            <select name="router_access_ids[]" id="router_access_ids" class="form-control select2" multiple>
                                {foreach $assignable_routers as $router}
                                    <option value="{$router['id']}">{$router['name']}</option>
                                {/foreach}
                            </select>
                            <span class="help-block">{Lang::T('Used when Router Access = List')}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group text-center">
        <button class="btn btn-primary" onclick="return ask(this, 'Continue the process of adding Admin?')" type="submit">{Lang::T('Save Changes')}</button>
        Or <a href="{Text::url('settings/users')}">{Lang::T('Cancel')}</a>
    </div>
</form>
{literal}
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

        function applyRootByRole(role) {
            var actorRole = getValue('ctx_actor_role');
            var selfAdminId = getValue('ctx_self_admin_id');
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
            var role = field && field.value ? field.value : '';
            var actorRole = getValue('ctx_actor_role');

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

            var modeWrap = byId('routerModeWrap');
            var modeSelect = byId('router_access_mode');
            if (role === 'SuperAdmin') {
                setHidden('routerModeWrap', true);
                setHidden('routerListWrap', true);
                if (modeSelect) {
                    modeSelect.value = 'all';
                }
                clearRouterSelection();
            } else if (modeWrap) {
                setHidden('routerModeWrap', false);
                toggleRouterList();
            }

            applyRootByRole(role);
        }

        window.checkUserType = checkUserType;

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
        });

        window.addEventListener('load', function () {
            refreshRouterSelect2();
            toggleRouterList();
        });
    })();
</script>
{/literal}

{include file="sections/footer.tpl"}
