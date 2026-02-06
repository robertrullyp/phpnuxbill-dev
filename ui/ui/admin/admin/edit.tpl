{include file="sections/header.tpl"}
<!-- user-edit -->

<form class="form-horizontal" method="post" enctype="multipart/form-data" role="form"
    action="{Text::url('settings/users-edit-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
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
                            <input type="number" class="form-control" id="phone" name="phone" value="{$d['phone']}">
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
                                        <option value="Sales" {if $d['user_type'] eq 'Sales'}selected="selected" {/if}>Sales
                                        </option>
                                    {/if}
                                    {if $_admin['user_type'] eq 'SuperAdmin'}
                                        <option value="Admin" {if $d['user_type'] eq 'Admin'}selected="selected" {/if}>
                                            Administrator</option>
                                        <option value="SuperAdmin" {if $d['user_type'] eq 'SuperAdmin'}selected="selected"
                                            {/if}>Super Administrator</option>
                                    {/if}
                                </select>
                            </div>
                        </div>
                        <div class="form-group {if $d['user_type'] neq 'Sales'}hidden{/if}" id="agentChooser">
                            <label class="col-md-3 control-label">{Lang::T('Agent')}</label>
                            <div class="col-md-9">
                                <select name="root" id="root" class="form-control">
                                    {foreach $agents as $agent}
                                        <option value="{$agent['id']}">{$agent['username']} | {$agent['fullname']} |
                                            {$agent['phone']}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Username')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="username" name="username"
                                value="{$d['username']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="password" name="password">
                            <span class="help-block">{Lang::T('Keep Blank to do not change Password')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Password')}</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" id="cpassword" name="cpassword"
                                placeholder="{Lang::T('Confirm Password')}">
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
    function checkUserType($field) {
        if ($field.value == 'Sales') {
            $('#agentChooser').removeClass('hidden');
        } else {
            $('#agentChooser').addClass('hidden');
        }
    }

    function deletePhoto(id) {
        if (confirm('Delete photo?')) {
            if (confirm('Are you sure to delete photo?')) {
                window.location.href = '{Text::url('settings/users-edit/')}'+id+'/deletePhoto'
            }
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

    (function () {
        var button = document.getElementById('admin_api_key_generate');
        if (!button) {
            return;
        }
        button.addEventListener('click', function () {
            var input = document.getElementById('admin_api_key');
            if (!input) {
                return;
            }
            input.value = generateAdminApiKey(40);
            var clearBox = document.querySelector('input[name="admin_api_key_clear"]');
            if (clearBox) {
                clearBox.checked = false;
            }
            input.focus();
            input.select();
        });
    })();

    (function () {
        var group = document.querySelector('.js-api-key-hover');
        var input = document.getElementById('admin_api_key');
        if (!group || !input) {
            return;
        }
        var originalType = input.type;
        group.addEventListener('mouseenter', function () {
            input.type = 'text';
        });
        group.addEventListener('mouseleave', function () {
            input.type = originalType || 'password';
        });
    })();
</script>
{include file="sections/footer.tpl"}
