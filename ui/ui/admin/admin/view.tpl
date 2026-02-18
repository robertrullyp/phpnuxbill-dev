{include file="sections/header.tpl"}
<!-- user-edit -->

<form class="form-horizontal">
    <div class="row">
        {if $parent_user}<div class="col-sm-6 col-md-6">{else}<div class="col-md-6 col-md-offset-3">{/if}
                <div
                    class="panel panel-{if $d['status'] != 'Active'}danger{else}primary{/if} panel-hovered panel-stacked mb30">
                    <div class="panel-heading">{$d['fullname']}</div>
                    <div class="panel-body">
                        <center>
                            {assign var='adminPhotoPath' value=$d['photo']}
                            {assign var='adminPhotoFallback' value=$app_url|cat:'/'|cat:$UPLOAD_PATH|cat:'/admin.default.png'}
                            {if !$adminPhotoPath || strstr($adminPhotoPath, 'default')}
                                {assign var='adminPhotoSrc' value=$adminPhotoFallback}
                            {else}
                                {assign var='cleanAdminPhoto' value=$adminPhotoPath|trim:'/'}
                                {assign var='adminPhotoSrc' value=$app_url|cat:'/'|cat:$UPLOAD_PATH|cat:'/'|cat:$cleanAdminPhoto|cat:'.thumb.jpg'}
                            {/if}
                            <a href="{$app_url}/{$UPLOAD_PATH}{$d['photo']}" target="foto">
                                <img src="{$adminPhotoSrc}" width="200"
                                    onerror="this.src='{$adminPhotoFallback}'" class="img-circle img-responsive" alt="Foto">
                            </a>
                        </center><br>
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item">
                                <b>{Lang::T('Username')}</b> <span class="pull-right">{$d['username']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Phone Number')}</b> <span class="pull-right">{$d['phone']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Email')}</b> <span class="pull-right">{$d['email']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('City')}</b> <span class="pull-right">{$d['city']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Sub District')}</b> <span class="pull-right">{$d['subdistrict']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Ward')}</b> <span class="pull-right">{$d['ward']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('User Type')}</b> <span class="pull-right">{$d['user_type']}</span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Parent')}</b>
                                <span class="pull-right">
                                    {if $parent_user}
                                        {$parent_user['fullname']} ({$parent_user['user_type']})
                                    {else}
                                        -
                                    {/if}
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>{Lang::T('Router Access')}</b>
                                <span class="pull-right">{if $router_assignment_mode eq 'list'}{Lang::T('List')}{else}{Lang::T('All')}{/if}</span>
                            </li>
                            {if $router_assignment_mode eq 'list'}
                                <li class="list-group-item">
                                    <b>{Lang::T('Allowed Routers')}</b>
                                    <span class="pull-right">
                                        {if $router_assignment_rows|@count > 0}
                                            {foreach $router_assignment_rows as $router}
                                                <span class="label label-info">{$router['name']}</span>
                                            {/foreach}
                                        {else}
                                            -
                                        {/if}
                                    </span>
                                </li>
                            {/if}
                            <li class="list-group-item">
                                <b>{Lang::T('Admin API Key')}</b>
                                <span class="pull-right">
                                    {if $admin_api_key_set}{Lang::T('Set')}{else}{Lang::T('Not Set')}{/if}
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="panel-footer">
                        <center><a href="{Text::url('settings/users-edit/', $d['id'])}"
                                class="btn btn-info btn-block">{Lang::T('Edit')}</a>
                            <a href="{Text::url('settings/users')}" class="btn btn-link btn-block">{Lang::T('Cancel')}</a>
                        </center>
                    </div>
                </div>
            </div>
            {if $parent_user}
                <div class="col-sm-6 col-md-6">
                    <div class="panel panel-success">
                        <div class="panel-heading">{Lang::T('Parent')} - {$parent_user['fullname']}</div>
                        <div class="panel-body">
                            <ul class="list-group list-group-unbordered">
                                <li class="list-group-item">
                                    <b>{Lang::T('Phone Number')}</b> <span class="pull-right"><a
                                            href="tel:{$parent_user['phone']}">{$parent_user['phone']}</a></span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Email')}</b> <span class="pull-right"><a
                                            href="mailto:{$parent_user['email']}">{$parent_user['email']}</a></span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('City')}</b> <span class="pull-right">{$parent_user['city']}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Sub District')}</b> <span class="pull-right">{$parent_user['subdistrict']}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Ward')}</b> <span class="pull-right">{$parent_user['ward']}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
</form>
{include file="sections/footer.tpl"}
