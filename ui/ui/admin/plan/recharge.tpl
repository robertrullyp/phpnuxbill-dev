{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{if $is_refund}{Lang::T('Refund Account')}{else}{Lang::T('Recharge Account')}{/if}</div>
            <div class="panel-body">
                <form id="{if $is_refund}refund-form{else}recharge-form{/if}" class="form-horizontal" method="post" role="form" action="{if $is_refund}{Text::url('')}plan/refund-confirm{else}{Text::url('')}plan/recharge-confirm{/if}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Select Account')}</label>
                        <div class="col-md-6">
                            <select id="{if $is_refund}refund_customer{else}personSelect{/if}" class="form-control select2"
                                name="id_customer" style="width: 100%"
                                data-placeholder="{Lang::T('Select a customer')}...">
                                {if $cust}
                                    <option value="{$cust['id']}">{$cust['username']} &bull; {$cust['fullname']} &bull;
                                        {$cust['email']}</option>
                                {/if}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}</label>
                        <div class="col-md-6">
                            <label><input type="radio" {if !$is_refund}id="Hot"{/if} name="{if $is_refund}refund_type{else}type{/if}" value="Hotspot">
                                {Lang::T('Hotspot Plans')}</label>
                            <label><input type="radio" {if !$is_refund}id="POE"{/if} name="{if $is_refund}refund_type{else}type{/if}" value="PPPOE">
                                {Lang::T('PPPOE Plans')}</label>
                            <label><input type="radio" {if !$is_refund}id="VPN"{/if} name="{if $is_refund}refund_type{else}type{/if}" value="VPN"> {Lang::T('VPN Plans')}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Routers')}</label>
                        <div class="col-md-6">
                            <select id="{if $is_refund}refund_server{else}server{/if}" data-type="server" name="server" class="form-control select2">
                                <option value=''>{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{if $is_refund}{Lang::T('Active Plan')}{else}{Lang::T('Service Plan')}{/if}</label>
                        <div class="col-md-6">
                            <select id="{if $is_refund}refund_plan{else}plan{/if}" name="plan" class="form-control select2">
                                <option value=''>{Lang::T('Select Plans')}</option>
                            </select>
                            {if $is_refund}
                                <p class="help-block">{Lang::T('Only active package in selected scope will be listed.')}</p>
                            {/if}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Using')}</label>
                        <div class="col-md-6">
                            <select name="using" class="form-control">
                                {foreach $usings as $using}
                                    <option value="{trim($using)}">{trim(ucWords($using))}</option>
                                {/foreach}
                                {if $_c['enable_balance'] eq 'yes'}
                                    <option value="balance">{Lang::T('Customer Balance')}</option>
                                {/if}
                                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                    <option value="zero">{$_c['currency_code']} 0</option>
                                {/if}
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {if $is_refund}
                                {Lang::T('Refund via Customer Balance will credit customer balance.')}
                            {else}
                                {Lang::T('Postpaid Recharge for the first time use')} {$_c['currency_code']} 0
                            {/if}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Note')}</label>
                        <div class="col-md-6">
                            <input type="text" name="note" class="form-control" placeholder="{Lang::T('Optional note')}">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Optional')}</p>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn {if $is_refund}btn-danger{else}btn-success{/if}"
                                onclick="return ask(this, '{if $is_refund}{Lang::T('Continue the Refund process')}{else}{Lang::T('Continue the Recharge process')}{/if}?')"
                                type="submit">{if $is_refund}{Lang::T('Refund')}{else}{Lang::T('Recharge')}{/if}</button>
                            {Lang::T('Or')} <a href="{Text::url('')}customers/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
