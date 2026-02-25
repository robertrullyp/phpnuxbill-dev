{include file="sections/header.tpl"}
<style>
    .panel-title {
        font-weight: bolder;
        font-size: large;
    }
</style>

<form class="form-horizontal" id="settings_app_form" method="post" role="form" action="{Text::url('')}settings/app-post"
    enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="panel" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel-heading" role="tab" id="General">
            <h3 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseGeneral"
                    aria-expanded="true" aria-controls="collapseGeneral">
                    {Lang::T('General')}
                </a>
            </h3>
        </div>
        <div id="collapseGeneral" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Application Name / Company Name')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="CompanyName" name="CompanyName"
                            value="{$_c['CompanyName']}">
                    </div>
                    <span class="help-block col-md-4">{Lang::T('This Name will be shown on the Title')}</span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Company Logo')}</label>
                    <div class="col-md-5">
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <span
                            class="help-block">{Lang::T('For PDF Reports | Best size 1078 x 200 | uploaded image will be autosize')}</span>
                    </div>
                    <span class="help-block col-md-4">
                        <a href="{$app_url}/{$logo|replace:'\\':'/'}" target="_blank"><img src="{$app_url}/{$logo|replace:'\\':'/'}" height="48" alt="logo for PDF"></a>
                    </span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Company Footer')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="CompanyFooter" name="CompanyFooter"
                            value="{$_c['CompanyFooter']}">
                    </div>
                    <span class="help-block col-md-4">{Lang::T('Will show below user pages')}</span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Address')}</label>
                    <div class="col-md-5">
                        <textarea class="form-control" id="address" name="address"
                            rows="3">{Lang::htmlspecialchars($_c['address'])}</textarea>
                    </div>
                    <span class="help-block col-md-4">{Lang::T('You can use html tag')}</span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Phone Number')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="phone" name="phone" value="{$_c['phone']}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Invoice Footer')}</label>
                    <div class="col-md-5">
                        <textarea class="form-control" id="note" name="note"
                            rows="3">{Lang::htmlspecialchars($_c['note'])}</textarea>
                        <span class="help-block">{Lang::T('You can use html tag')}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Show Invoice/Transaction Note')}</label>
                    <div class="col-md-5">
                        <select name="show_invoice_note" class="form-control">
                            <option value="no">{Lang::T('No')}</option>
                            <option value="yes" {if $_c['show_invoice_note']=='yes'}selected="selected"{/if}>{Lang::T('Yes')}</option>
                        </select>
                    </div>
                    <span class="help-block col-md-4">{Lang::T('Show note on invoice view and transaction reports')}</span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label"><i class="glyphicon glyphicon-print"></i>
                        {Lang::T('Print Max Char')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="printer_cols" placeholder="37"
                            name="printer_cols" value="{if empty($_c['printer_cols'])}37{else}{$_c['printer_cols']}{/if}">
                    </div>
                    <span class="help-block col-md-4">{Lang::T('For invoice print using Thermal
                        Printer')}</span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Theme')}</label>
                    <div class="col-md-5">
                        <select name="theme" id="theme" class="form-control">
                            <option value="default" {if $_c['theme'] eq 'default' }selected="selected" {/if}>
                                {Lang::T('Default')}
                            </option>
                            {foreach $themes as $theme}
                                <option value="{$theme}" {if $_c['theme'] eq $theme}selected="selected" {/if}>
                                    {Lang::ucWords($theme)}</option>
                            {/foreach}
                        </select>
                    </div>
                    <p class="help-block col-md-4"><a href="https://github.com/hotspotbilling/phpnuxbill/wiki/Themes"
                            target="_blank">{Lang::T('Theme Info')}</a></p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Recharge Using')}</label>
                    <div class="col-md-5">
                        <input type="text" name="payment_usings" class="form-control" value="{$_c['payment_usings']}"
                            placeholder="{Lang::T('Cash')}, {Lang::T('Bank Transfer')}">
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('This used for admin to select payment in recharge, using comma for every new options')}
                    </p>
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Income reset date')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="reset_day" placeholder="20" min="1"
                            max="28" step="1" name="reset_day" value="{$_c['reset_day']}">
                    </div>
                    <span class="help-block col-md-4">{Lang::T('Income will reset every this day')}</span>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Dashboard Structure')}</label>
                    <div class="col-md-5">
                        <input type="text" name="dashboard_cr" class="form-control" value="{$_c['dashboard_cr']}"
                            placeholder="12.7,5.12">
                    </div>
                    <p class="help-block col-md-4">
                        <a href="{$app_url}/docs/#Dashboard%20Structure"
                            target="_blank">{Lang::T('Read documentation')}</a>
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Pretty URL')}</label>
                    <div class="col-md-5">
                        <select name="url_canonical" id="url_canonical" class="form-control">
                            <option value="no" {if $_c['url_canonical']=='no' }selected="selected" {/if}>
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['url_canonical']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                        <p class="help-block">
                            <b>?_route=settings/app&foo=bar</b> will be <b>/settings/app?foo=bar</b>
                        </p>
                    </div>
                    <span class="help-block col-md-4">{Lang::T('rename .htaccess_firewall to .htaccess')}</span>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" name="general" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>

        </div>
    </div>

    <div class="panel" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel-heading" role="tab" id="AcsIntegration">
            <h3 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseAcsIntegration"
                    aria-expanded="true" aria-controls="collapseAcsIntegration">
                    ACS Integration
                </a>
            </h3>
        </div>
        <div id="collapseAcsIntegration" class="panel-collapse collapse" role="tabpanel"
            aria-labelledby="headingAcsIntegration">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Integration')}</label>
                    <div class="col-md-5">
                        <select name="genieacs_enable" id="genieacs_enable" class="form-control">
                            <option value="no" {if ($_c['genieacs_enable']|default:'no')=='no' }selected="selected" {/if}>
                                {Lang::T('Disable')}
                            </option>
                            <option value="yes" {if ($_c['genieacs_enable']|default:'no')=='yes' }selected="selected" {/if}>
                                {Lang::T('Enable')}
                            </option>
                        </select>
                    </div>
                    <span class="help-block col-md-4">GenieACS (TR-069/TR-181)</span>
                </div>
                <div class="form-group" id="genieacs_url_wrap">
                    <label class="col-md-3 control-label">GenieACS URL</label>
                    <div class="col-md-5">
                        <input type="url" class="form-control" id="genieacs_url" name="genieacs_url"
                            value="{($_c['genieacs_url']|default:'')|escape}" placeholder="http://localhost:7557">
                    </div>
                    <span class="help-block col-md-4">{Lang::T('Example')}: http://localhost:7557</span>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel-heading" role="tab" id="LoginPage">
            <h3 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseLoginPage"
                    aria-expanded="true" aria-controls="collapseLoginPage">
                    {Lang::T('Customer Login Page')}
                </a>
            </h3>
        </div>
        <div id="collapseLoginPage" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Choose Template')}</label>
                    <div class="col-md-5">
                        <select name="login_page_type" id="login_page_type" class="form-control">
                            <option value="default" {if $_c['login_page_type']=='default' }selected="selected" {/if}>
                                {Lang::T('Default')}</option>
                            <option value="custom" {if $_c['login_page_type']=='custom' }selected="selected" {/if}>
                                {Lang::T('Custom')}</option>
                        </select>
                    </div>
                    <span class="help-block col-md-4"><small>{Lang::T('Select your login template type')}</small></span>
                </div>
                <div id="customFields" style="display: none;">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Select Login Page')}</label>
                        <div class="col-md-5">
                            <select name="login_Page_template" id="login_Page_template" class="form-control">
                                {foreach $template_files as $template}
                                    <option value="{$template.value|escape}"
                                        {if $_c['login_Page_template'] eq $template.value}selected="selected" {/if}>
                                        {$template.name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                        <span
                            class="help-block col-md-4"><small>{Lang::T('Select your preferred login template')}</small></span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Page Heading / Company Name')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="login_page_head" name="login_page_head"
                                value="{$_c['login_page_head']}">
                        </div>
                        <span
                            class="help-block col-md-4"><small>{Lang::T('This Name will be shown on the login wallpaper')}</small></span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Page Description')}</label>
                        <div class="col-md-5">
                            <textarea class="form-control" id="login_page_description" name="login_page_description"
                                rows="3">{Lang::htmlspecialchars($_c['login_page_description'])}</textarea>
                        </div>
                        <span
                            class="help-block col-md-4"><small>{Lang::T('This will also display on wallpaper, You can use html tag')}</small></span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Favicon')}</label>
                        <div class="col-md-5">
                            <input type="file" class="form-control" id="login_page_favicon" name="login_page_favicon"
                                accept="image/*">
                            <span
                                class="help-block"><small>{Lang::T('Best size 30 x 30 | uploaded image will be autosize')}</small></span>
                        </div>
                        <span class="help-block col-md-4">
                            <a href="./{$favicon}" target="_blank"><img src="./{$favicon}" height="48"
                                    alt="Favicon"></a>
                        </span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Login Page Logo')}</label>
                        <div class="col-md-5">
                            <input type="file" class="form-control" id="login_page_logo" name="login_page_logo"
                                accept="image/*">
                            <span
                                class="help-block"><small>{Lang::T('Best size 300 x 60 | uploaded image will be autosize')}</small></span>
                        </div>
                        <span class="help-block col-md-4">
                            <a href="{$app_url}/{$login_logo|replace:'\\':'/'}" target="_blank"><img src="{$app_url}/{$login_logo|replace:'\\':'/'}" height="48"
                                    alt="Logo"></a>
                        </span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Login Page Wallpaper')}</label>
                        <div class="col-md-5">
                            <input type="file" class="form-control" id="login_page_wallpaper"
                                name="login_page_wallpaper" accept="image/*">
                            <span
                                class="help-block"><small>{Lang::T('Best size 1920 x 1080 | uploaded image will be autosize')}</small></span>
                        </div>
                        <span class="help-block col-md-4">
                            <a href="{$app_url}/{$wallpaper|replace:'\\':'/'}" target="_blank"><img src="{$app_url}/{$wallpaper|replace:'\\':'/'}" height="48"
                                    alt="Wallpaper"></a>
                        </span>
                    </div>
                </div>

                <button class="btn btn-success btn-block" name="general" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Coupon">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseCoupon" aria-expanded="false" aria-controls="collapseCoupon">
                    {Lang::T('Coupons')}
                </a>
            </h4>
        </div>
        <div id="collapseCoupon" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Coupon')}</label>
                    <div class="col-md-5">
                        <select name="enable_coupons" id="enable_coupons" class="form-control text-muted">
                            <option value="no">{Lang::T('No')}</option>
                            <option value="yes" {if $_c['enable_coupons'] == 'yes'}selected="selected" {/if}>{Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        <small>{Lang::T('Enable or disable coupons')}</small>
                    </p>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Registration">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseRegistration" aria-expanded="false" aria-controls="collapseRegistration">
                    {Lang::T('Registration')}
                </a>
            </h4>
        </div>
        <div id="collapseRegistration" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Allow Registration')}</label>
                    <div class="col-md-5">
                        <select name="disable_registration" id="disable_registration" class="form-control">
                            <option value="no" {if $_c['disable_registration']=='no' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                            {if $_c['disable_voucher'] != 'yes'}
                                <option value="yes" {if $_c['disable_registration']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Voucher Only')}
                                </option>
                            {/if}
                            <option value="noreg" {if $_c['disable_registration']=='noreg' }selected="selected" {/if}>
                                {Lang::T('No Registration')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('Customer just Login with Phone number and Voucher Code, Voucher will be password')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Registration Method')}</label>
                    <div class="col-md-5">
                        <select name="registration_username" id="voucher_format" class="form-control">
                            <option value="username" {if $_c['registration_username']=='username' }selected="selected"
                                {/if}>{Lang::T('Usernames')}
                            </option>
                            <option value="email" {if $_c['registration_username']=='email' }selected="selected" {/if}>
                                Email
                            </option>
                            <option value="phone" {if $_c['registration_username']=='phone' }selected="selected" {/if}>
                                {Lang::T('Phone Number')}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Photo Required')}</label>
                    <div class="col-md-5">
                        <select name="photo_register" id="photo_register" class="form-control">
                            <option value="no">
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['photo_register']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('Customer Registration need to upload their photo')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('SMS OTP Registration')}</label>
                    <div class="col-md-5">
                        <select name="sms_otp_registration" id="sms_otp_registration" class="form-control">
                            <option value="no">
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['sms_otp_registration']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('Customer Registration need to validate using OTP')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('OTP Method')}</label>
                    <div class="col-md-5">
                        <select name="phone_otp_type" id="phone_otp_type" class="form-control">
                            <option value="sms" {if $_c['phone_otp_type']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="whatsapp" {if $_c['phone_otp_type']=='whatsapp' }selected="selected" {/if}>
                                {Lang::T('by WhatsApp')}</option>
                            <option value="both" {if $_c['phone_otp_type']=='both' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp and SMS')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('The method which OTP will be sent to user')}<br>
                        {Lang::T('For Registration and Update Phone Number')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Welcome Package')}</label>
                    <div class="col-md-5">
                        <select name="welcome_package_enable" class="form-control">
                            <option value="no">{Lang::T('No')}</option>
                            <option value="yes" {if $_c['welcome_package_enable']=='yes'}selected="selected"{/if}>{Lang::T('Yes')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Automatically recharge selected package for new users')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Welcome Package')}</label>
                    <div class="col-md-5">
                        <select name="welcome_package_plan" class="form-control">
                            <option value="">{Lang::T('None')}</option>
                            {foreach $plans as $pl}
                                <option value="{$pl['id']}" {if $_c['welcome_package_plan']==$pl['id']}selected="selected"{/if}>{$pl['name_plan']}{if $pl['enabled'] == 0} ({Lang::T('Inactive')}){/if}</option>
                            {/foreach}
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Package applied on successful registration')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Notify Admin')}</label>
                    <div class="col-md-5">
                        <select name="reg_nofify_admin" id="reg_nofify_admin" class="form-control">
                            <option value="no">
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['reg_nofify_admin']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('Notify Admin upon self registration')}
                    </p>
                </div>
	                <div class="form-group">
	                    <label class="col-md-3 control-label">{Lang::T('Send welcome message')}</label>
	                    <div class="col-md-5">
	                        <select name="reg_send_welcome_message" id="reg_send_welcome_message" class="form-control">
	                            <option value="no">
	                                {Lang::T('No')}
	                            </option>
	                            <option value="yes" {if isset($_c['reg_send_welcome_message']) && $_c['reg_send_welcome_message']=='yes' }selected="selected" {/if}>
	                                {Lang::T('Yes')}
	                            </option>
	                        </select>
	                    </div>
	                    <p class="help-block col-md-4">
	                        {Lang::T('Send Welcome Message template to customer after self registration')}
	                    </p>
	                </div>
	                <div class="form-group" id="reg_welcome_via_group" {if !isset($_c['reg_send_welcome_message']) || $_c['reg_send_welcome_message'] neq 'yes'}style="display:none;"{/if}>
	                    <label class="col-md-3 control-label">{Lang::T('Send via')}</label>
	                    <div class="col-md-5">
	                        <div class="row">
	                            <div class="col-xs-6 col-sm-3">
	                                <input type="hidden" name="reg_welcome_via_whatsapp" value="no">
	                                <div class="checkbox" style="margin:0;">
	                                    <label>
	                                        <input type="checkbox" name="reg_welcome_via_whatsapp" value="yes"
	                                            {if isset($_c['reg_welcome_via_whatsapp'])}
	                                                {if $_c['reg_welcome_via_whatsapp']=='yes'}checked{/if}
	                                            {else}
	                                                {if isset($_c['phone_otp_type']) && ($_c['phone_otp_type']=='whatsapp' || $_c['phone_otp_type']=='both')}checked{/if}
	                                            {/if}>
	                                        {Lang::T('WhatsApp')}
	                                    </label>
	                                </div>
	                            </div>
	                            <div class="col-xs-6 col-sm-3">
	                                <input type="hidden" name="reg_welcome_via_sms" value="no">
	                                <div class="checkbox" style="margin:0;">
	                                    <label>
	                                        <input type="checkbox" name="reg_welcome_via_sms" value="yes"
	                                            {if isset($_c['reg_welcome_via_sms'])}
	                                                {if $_c['reg_welcome_via_sms']=='yes'}checked{/if}
	                                            {else}
	                                                {if isset($_c['phone_otp_type']) && ($_c['phone_otp_type']=='sms' || $_c['phone_otp_type']=='both')}checked{/if}
	                                            {/if}>
	                                        {Lang::T('SMS')}
	                                    </label>
	                                </div>
	                            </div>
	                            <div class="col-xs-6 col-sm-3">
	                                <input type="hidden" name="reg_welcome_via_email" value="no">
	                                <div class="checkbox" style="margin:0;">
	                                    <label>
	                                        <input type="checkbox" name="reg_welcome_via_email" value="yes"
	                                            {if isset($_c['reg_welcome_via_email']) && $_c['reg_welcome_via_email']=='yes'}checked{/if}>
	                                        {Lang::T('Email')}
	                                    </label>
	                                </div>
	                            </div>
	                            <div class="col-xs-6 col-sm-3">
	                                <input type="hidden" name="reg_welcome_via_inbox" value="no">
	                                <div class="checkbox" style="margin:0;">
	                                    <label>
	                                        <input type="checkbox" name="reg_welcome_via_inbox" value="yes"
	                                            {if isset($_c['reg_welcome_via_inbox']) && $_c['reg_welcome_via_inbox']=='yes'}checked{/if}>
	                                        {Lang::T('Inbox')}
	                                    </label>
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <p class="help-block col-md-4">
	                        {Lang::T('Select channels for welcome message')}
	                    </p>
	                </div>
	                <div class="form-group">
	                    <label class="col-md-3 control-label">{Lang::T('Mandatory Fields')}:</label><br>
	                    <label class="col-md-3 control-label">
	                        <input type="checkbox" name="man_fields_email" value="yes"
	                            {if !isset($_c['man_fields_email']) || $_c['man_fields_email'] neq 'no'}checked{/if}>
                        {Lang::T('Email')}
                    </label>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="man_fields_fname" value="yes"
                            {if !isset($_c['man_fields_fname']) || $_c['man_fields_fname'] neq 'no'}checked{/if}>
                        {Lang::T('Full Name')}
                    </label>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="man_fields_address" value="yes"
                            {if !isset($_c['man_fields_address']) || $_c['man_fields_address'] neq 'no'}checked{/if}>
                        {Lang::T('Address')}
                    </label>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Security">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseSecurity" aria-expanded="false" aria-controls="collapseSecurity">
                    {Lang::T('Security')}
                </a>
            </h4>
        </div>
        <div id="collapseSecurity" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <hr class="clearfix">
                <h4 class="col-md-12" style="margin-top:0">{Lang::T('Session & Timeout')}</h4>
                <div class="form-group">
                <label class="col-md-3 control-label">{Lang::T('Enable Session Timeout')}</label>
                <div class="col-md-5">
                    <label class="switch">
                    <input type="checkbox" id="enable_session_timeout" value="1" name="enable_session_timeout"
                            {if $_c['enable_session_timeout']==1}checked{/if}>
                    <span class="slider"></span>
                    </label>
                </div>
                <p class="help-block col-md-4">{Lang::T('Logout Admin if not Available/Online a period of time')}</p>
                </div>
    
                <div class="form-group" id="timeout_duration_input" style="display: none;">
                <label class="col-md-3 control-label">{Lang::T('Timeout Duration')}</label>
                <div class="col-md-5">
                    <input type="number" value="{$_c['session_timeout_duration']}" class="form-control"
                        name="session_timeout_duration" id="session_timeout_duration"
                        placeholder="{Lang::T('Enter the session timeout duration (minutes)')}" min="1">
                </div>
                <p class="help-block col-md-4">{Lang::T('Idle Timeout, Logout Admin if Idle for xx minutes')}</p>
                </div>
    
                <div class="form-group">
                <label class="col-md-3 control-label">{Lang::T('Single Admin Session')}</label>
                <div class="col-md-5">
                    <select name="single_session" id="single_session" class="form-control">
                    <option value="no">{Lang::T('No')}</option>
                    <option value="yes" {if $_c['single_session']=='yes' }selected="selected" {/if}>{Lang::T('Yes')}</option>
                    </select>
                </div>
                <p class="help-block col-md-4">{Lang::T('Admin can only have single session login, it will logout another session')}</p>
                </div>
    
                <hr class="clearfix">
                <h4 class="col-md-12" style="margin-top:0">{Lang::T('Cross Site Request Forgery')}</h4>
                <div class="form-group">
                <label class="col-md-3 control-label">{Lang::T('Enable CSRF Validation')}</label>
                <div class="col-md-5">
                    <select name="csrf_enabled" id="csrf_enabled" class="form-control">
                    <option value="no">{Lang::T('No')}</option>
                    <option value="yes" {if $_c['csrf_enabled']=='yes' }selected="selected" {/if}>{Lang::T('Yes')}</option>
                    </select>
                </div>
                <p class="help-block col-md-4">
                    <a href="https://en.wikipedia.org/wiki/Cross-site_request_forgery" target="_blank">{Lang::T('Cross-site request forgery')}</a>
                </p>
                </div>
    
                <hr class="clearfix">
                <h4 class="col-md-12" style="margin-top:0">{Lang::T('Cloudflare Turnstile')}</h4>
    
                <div class="form-group">
                <label class="col-md-3 control-label">Turnstile Site Key</label>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="turnstile_site_key"
                        value="{$_c['turnstile_site_key']|escape}" placeholder="1x0000..." autocomplete="off">
                </div>
                <p class="help-block col-md-4">{Lang::T('Public key used for verification on the client side.')}</p>
                </div>
    
                <div class="form-group">
                <label class="col-md-3 control-label">Turnstile Secret Key</label>
                <div class="col-md-5">
                    <input type="password" class="form-control" name="turnstile_secret_key"
                        onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                        value="{$_c['turnstile_secret_key']|escape}" placeholder="{Lang::T('Leave blank to keep unchanged')}" autocomplete="off">
                </div>
                <p class="help-block col-md-4"><a href="https://developers.cloudflare.com/turnstile/" target="_blank">{Lang::T('Learn more about Turnstile')}</a></p>
                </div>
    
                <div class="form-group">
                <label class="col-md-3 control-label">{Lang::T('Enable on Admin Login')}</label>
                <div class="col-md-5">
                    <select name="turnstile_admin_enabled" id="turnstile_admin_enabled" class="form-control">
                    <option value="0">{Lang::T('No')}</option>
                    <option value="1" {if $_c['turnstile_admin_enabled']=='1'}selected="selected"{/if}>{Lang::T('Yes')}</option>
                    </select>
                </div>
                <p class="help-block col-md-4">{Lang::T('Enable Turnstile for admin login page.')}</p>
                </div>
    
                <div class="form-group">
                <label class="col-md-3 control-label">{Lang::T('Enable on Customer Login')}</label>
                <div class="col-md-5">
                    <select name="turnstile_client_enabled" id="turnstile_client_enabled" class="form-control">
                    <option value="0">{Lang::T('No')}</option>
                    <option value="1" {if $_c['turnstile_client_enabled']=='1'}selected="selected"{/if}>{Lang::T('Yes')}</option>
                    </select>
                </div>
                <p class="help-block col-md-4">{Lang::T('Enable Turnstile for customer login page.')}</p>
                </div>
    
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                {Lang::T('Save Changes')}
                </button>
            </div>
        </div>

        
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Voucher">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseVoucher" aria-expanded="false" aria-controls="collapseVoucher">
                    Voucher
                </a>
            </h4>
        </div>
        <div id="collapseVoucher" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Disable Voucher')}</label>
                    <div class="col-md-5">
                        <select name="disable_voucher" id="disable_voucher" class="form-control">
                            <option value="no" {if $_c['disable_voucher']=='no' }selected="selected" {/if}>
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['disable_voucher']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Voucher activation menu will be hidden')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Voucher Format')}</label>
                    <div class="col-md-5">
                        <select name="voucher_format" id="voucher_format" class="form-control">
                            <option value="up" {if $_c['voucher_format']=='up' }selected="selected" {/if}>UPPERCASE
                            </option>
                            <option value="low" {if $_c['voucher_format']=='low' }selected="selected" {/if}>
                                lowercase
                            </option>
                            <option value="rand" {if $_c['voucher_format']=='rand' }selected="selected" {/if}>
                                RaNdoM
                            </option>
                            <option value="numbers" {if $_c['voucher_format']=='numbers' }selected="selected" {/if}>
                                Numbers
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">UPPERCASE lowercase RaNdoM</p>
                </div>
                {if $_c['disable_voucher'] != 'yes'}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Redirect URL after Activation')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="voucher_redirect" name="voucher_redirect"
                                placeholder="https://192.168.88.1/status" value="{$_c['voucher_redirect']}">
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('After Customer activate voucher or login, customer will be redirected to this
                        url')}
                        </p>
                    </div>
                {/if}
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="FreeRadius">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseFreeRadius" aria-expanded="false" aria-controls="collapseFreeRadius">
                    FreeRadius
                </a>
            </h4>
        </div>
        <div id="collapseFreeRadius" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Radius')}</label>
                    <div class="col-md-5">
                        <select name="radius_enable" id="radius_enable" class="form-control text-muted">
                            <option value="0">{Lang::T('No')}</option>
                            <option value="1" {if $_c['radius_enable']}selected="selected" {/if}>{Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4"><a
                            href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius"
                            target="_blank">{Lang::T('Radius Instructions')}</a></p>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="ExtendPostpaidExpiration">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseExtendPostpaidExpiration" aria-expanded="false"
                    aria-controls="collapseExtendPostpaidExpiration">
                    {Lang::T('Extend Expiration')}
                </a>
            </h4>
        </div>
        <div id="collapseExtendPostpaidExpiration" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Allow Extend')}</label>
                    <div class="col-md-5">
                        <select name="extend_expired" id="extend_expired" class="form-control text-muted">
                            <option value="0">{Lang::T('No')}</option>
                            <option value="1" {if $_c['extend_expired']==1}selected="selected" {/if}>
                                {Lang::T('Yes')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Customer can request to extend expirations')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Allow Prepaid Extend')}</label>
                    <div class="col-md-5">
                        <select name="extend_allow_prepaid" id="extend_allow_prepaid" class="form-control text-muted">
                            <option value="0">{Lang::T('No')}</option>
                            <option value="1" {if $_c['extend_allow_prepaid']==1 || $_c['extend_allow_prepaid']=='1' || $_c['extend_allow_prepaid']=='yes'}selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('If enabled, prepaid plans can use Extend from customer portal')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Extend Days')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="extend_days" placeholder="3"
                            value="{$_c['extend_days']}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Confirmation Message')}</label>
                    <div class="col-md-5">
                        <textarea type="text" rows="4" class="form-control" name="extend_confirmation"
                            placeholder="{Lang::T('i agree to extends and will paid full after this')}">{$_c['extend_confirmation']}</textarea>
                    </div>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="CustomerBalanceSystem">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseCustomerBalanceSystem" aria-expanded="false"
                    aria-controls="collapseCustomerBalanceSystem">
                    {Lang::T('Customer Balance System')}
                </a>
            </h4>
        </div>
        <div id="collapseCustomerBalanceSystem" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable System')}</label>
                    <div class="col-md-5">
                        <select name="enable_balance" id="enable_balance" class="form-control">
                            <option value="no" {if $_c['enable_balance']=='no' }selected="selected" {/if}>
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['enable_balance']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Customer can deposit money to buy voucher')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Allow Transfer')}</label>
                    <div class="col-md-5">
                        <select name="allow_balance_transfer" id="allow_balance_transfer" class="form-control">
                            <option value="no" {if $_c['allow_balance_transfer']=='no' }selected="selected" {/if}>
                                {Lang::T('No')}</option>
                            <option value="yes" {if $_c['allow_balance_transfer']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Allow balance transfer between customers')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Minimum Balance Transfer')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="minimum_transfer" name="minimum_transfer"
                            value="{$_c['minimum_transfer']}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Allow Balance Custom
                        Amount')}</label>
                    <div class="col-md-5">
                        <select name="allow_balance_custom" id="allow_balance_custom" class="form-control">
                            <option value="no">
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['allow_balance_custom']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4"><small>
                            {Lang::T('Allow Customer buy balance with any amount')}
                        </small>
                    </p>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="NotificationSystem">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseNotificationSystem" aria-expanded="false" aria-controls="collapseNotificationSystem">
                    Sistem Pemberitahuan
                </a>
            </h4>
        </div>
        <div id="collapseNotificationSystem" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="panel-group" id="notificationAccordion" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading" role="tab" id="TelegramNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#notificationAccordion"
                    href="#collapseTelegramNotification" aria-expanded="false"
                    aria-controls="collapseTelegramNotification">
                    {Lang::T('Telegram Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testTg()">Test TG</a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseTelegramNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Telegram Bot Token')}</label>
                    <div class="col-md-5">
                        <input type="password" class="form-control" id="telegram_bot" name="telegram_bot"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                            value="{$_c['telegram_bot']}" placeholder="123456:asdasgdkuasghddlashdashldhalskdklasd">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Telegram User/Channel/Group ID')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="telegram_target_id" name="telegram_target_id"
                            value="{$_c['telegram_target_id']}" placeholder="12345678">
                    </div>
                </div>
                <small id="emailHelp" class="form-text text-muted">
                    {Lang::T('You will get Payment and Error notification')}
                </small>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="SMSNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#notificationAccordion"
                    href="#collapseSMSNotification" aria-expanded="false" aria-controls="collapseSMSNotification">
                    {Lang::T('SMS Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testSms()">
                            {Lang::T('Test SMS')}
                        </a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseSMSNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('SMS Server URL')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="sms_url" name="sms_url" value="{$_c['sms_url']}"
                            placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                        {Lang::T('it will be replaced.')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Or use Mikrotik SMS')}</label>
                    <div class="col-md-5">
                        <select class="form-control" onchange="document.getElementById('sms_url').value = this.value">
                            <option value="">{Lang::T('Select Router')}</option>
                            {foreach $r as $rs}
                                <option value="{$rs['name']}" {if $rs['name']==$_c['sms_url']}selected{/if}>
                                    {$rs['name']}</option>
                            {/foreach}
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                        {Lang::T('it will be replaced.')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Mikrotik SMS Command')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="mikrotik_sms_command" name="mikrotik_sms_command"
                            value="{$_c['mikrotik_sms_command']}" placeholder="mikrotik_sms_command">
                    </div>
                </div>
                <small id="emailHelp" class="form-text text-muted">{Lang::T('You can use')} WhatsApp
                    {Lang::T('in here too.')} <a href="https://gateway.drnet.biz.id" target="_blank">{Lang::T('Free
                        Server')}</a></small>

                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="WhatsappNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#notificationAccordion"
                    href="#collapseWhatsappNotification" aria-expanded="false"
                    aria-controls="collapseWhatsappNotification">
                    {Lang::T('Whatsapp Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testWa()">Test WA</a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseWhatsappNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                {assign var=wa_method value=$_c['wa_gateway_method']}
                {if $wa_method==''}
                    {if $_c['wa_gateway_url']!=''}
                        {assign var=wa_method value='post'}
                    {elseif $_c['wa_url']!=''}
                        {assign var=wa_method value='get'}
                    {else}
                        {assign var=wa_method value='post'}
                    {/if}
                {/if}
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Method')}</label>
                    <div class="col-md-5">
                        <select name="wa_gateway_method" id="wa_gateway_method" class="form-control">
                            <option value="post" {if $wa_method=='post'}selected="selected" {/if}>POST</option>
                            <option value="get" {if $wa_method=='get'}selected="selected" {/if}>GET</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Choose POST to use WA Gateway or GET for legacy URL')}</p>
                </div>
                <div id="wa_gateway_post_fields" {if $wa_method=='get'}style="display:none"{/if}>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('WhatsApp Gateway URL')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="wa_gateway_url" name="wa_gateway_url"
                                value="{$_c['wa_gateway_url']}" placeholder="https://your-host/ext/SECRET/wa">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('POST will be sent to')} <b>/ext/:secret/wa</b></p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('WhatsApp Auth Type')}</label>
                        <div class="col-md-5">
                            <select name="wa_gateway_auth_type" id="wa_gateway_auth_type" class="form-control">
                                <option value="none" {if $_c['wa_gateway_auth_type']=='' || $_c['wa_gateway_auth_type']=='none'}selected="selected" {/if}>None</option>
                                <option value="basic" {if $_c['wa_gateway_auth_type']=='basic'}selected="selected" {/if}>Basic</option>
                                <option value="header" {if $_c['wa_gateway_auth_type']=='header'}selected="selected" {/if}>Header</option>
                                <option value="jwt" {if $_c['wa_gateway_auth_type']=='jwt'}selected="selected" {/if}>JWT</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Authentication method for POST')}</p>
                    </div>
                    <div class="form-group wa-auth-field" data-auth="basic">
                        <label class="col-md-3 control-label">{Lang::T('Auth Username')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="wa_gateway_auth_username" name="wa_gateway_auth_username"
                                value="{$_c['wa_gateway_auth_username']}" placeholder="username">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Required for Basic auth')}</p>
                    </div>
                    <div class="form-group wa-auth-field" data-auth="basic">
                        <label class="col-md-3 control-label">{Lang::T('Auth Password')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="wa_gateway_auth_password" name="wa_gateway_auth_password"
                                value="{$_c['wa_gateway_auth_password']}" onmouseleave="this.type = 'password'"
                                onmouseenter="this.type = 'text'" placeholder="password">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Required for Basic auth')}</p>
                    </div>
                    <div class="form-group wa-auth-field" data-auth="header">
                        <label class="col-md-3 control-label">{Lang::T('Auth Header Name')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="wa_gateway_auth_header_name" name="wa_gateway_auth_header_name"
                                value="{$_c['wa_gateway_auth_header_name']}" placeholder="X-Api-Key">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Required for Header auth')}</p>
                    </div>
                    <div class="form-group wa-auth-field" data-auth="header jwt">
                        <label class="col-md-3 control-label">{Lang::T('Auth Token')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="wa_gateway_auth_token" name="wa_gateway_auth_token"
                                value="{$_c['wa_gateway_auth_token']}" onmouseleave="this.type = 'password'"
                                onmouseenter="this.type = 'text'" placeholder="token">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Required for Header or JWT auth')}</p>
                    </div>
                </div>
                <div id="wa_gateway_get_fields" {if $wa_method!='get'}style="display:none"{/if}>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('WhatsApp Gateway URL')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="wa_url" name="wa_url" value="{$_c['wa_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                            {Lang::T('it will be replaced.')}</p>
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('WA Queue Max Retries')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="wa_queue_max_retries" name="wa_queue_max_retries"
                            value="{if $_c['wa_queue_max_retries']!=''}{$_c['wa_queue_max_retries']}{else}3{/if}" min="1" max="20">
                    </div>
                    <p class="help-block col-md-4">Jumlah retry otomatis saat queue aktif.</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('WA Queue Retry Interval (seconds)')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="wa_queue_retry_interval" name="wa_queue_retry_interval"
                            value="{if $_c['wa_queue_retry_interval']!=''}{$_c['wa_queue_retry_interval']}{else}60{/if}" min="10" max="86400">
                    </div>
                    <p class="help-block col-md-4">Jeda waktu antar retry (detik). Diproses via cron.</p>
                </div>
                <small id="emailHelp" class="form-text text-muted">{Lang::T('You can use')} WhatsApp
                    {Lang::T('in here too.')} <a href="https://gateway.drnet.biz.id" target="_blank">{Lang::T('Free
                        Server')}</a></small>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="EmailNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#notificationAccordion"
                    href="#collapseEmailNotification" aria-expanded="false" aria-controls="collapseEmailNotification">
                    {Lang::T('Email Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testEmail()">Test
                            Email</a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseEmailNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">SMTP Host : Port</label>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                            value="{$_c['smtp_host']}" placeholder="smtp.host.tld">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                            value="{$_c['smtp_port']}" placeholder="465 587 port">
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Empty this to use internal mail() PHP')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('SMTP Username')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                            value="{$_c['smtp_user']}" placeholder="user@host.tld">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('SMTP Password')}</label>
                    <div class="col-md-5">
                        <input type="password" class="form-control" id="smtp_pass" name="smtp_pass"
                            value="{$_c['smtp_pass']}" onmouseleave="this.type = 'password'"
                            onmouseenter="this.type = 'text'">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('SMTP Security')}</label>
                    <div class="col-md-5">
                        <select name="smtp_ssltls" id="smtp_ssltls" class="form-control">
                            <option value="" {if $_c['smtp_ssltls']=='' }selected="selected" {/if}>Not Secure
                            </option>
                            <option value="ssl" {if $_c['smtp_ssltls']=='ssl' }selected="selected" {/if}>SSL
                            </option>
                            <option value="tls" {if $_c['smtp_ssltls']=='tls' }selected="selected" {/if}>TLS
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">UPPERCASE lowercase RaNdoM</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">Mail {Lang::T('From')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="mail_from" name="mail_from"
                            value="{$_c['mail_from']}" placeholder="noreply@host.tld">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Mail Reply To')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="mail_reply_to" name="mail_reply_to"
                            value="{$_c['mail_reply_to']}" placeholder="support@host.tld">
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('Customer will reply email to this address, empty if you want to use From
                        Address')}
                    </p>
                </div>

                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="UserNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseUserNotification" aria-expanded="false" aria-controls="collapseUserNotification">
                    {Lang::T('User Notification')}
                </a>
            </h4>
        </div>
        <div id="collapseUserNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Expired Notification')}</label>
                    <div class="col-md-5">
                        <select name="user_notification_expired" id="user_notification_expired" class="form-control">
                            <option value="none">{Lang::T('None')}</option>
                            <option value="wa" {if $_c['user_notification_expired']=='wa' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp')}</option>
                            <option value="sms" {if $_c['user_notification_expired']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="email" {if $_c['user_notification_expired']=='email' }selected="selected"
                                {/if}>{Lang::T('By Email')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('User will get notification when package expired')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Payment Notification')}</label>
                    <div class="col-md-5">
                        <select name="user_notification_payment" id="user_notification_payment" class="form-control">
                            <option value="none">{Lang::T('None')}</option>
                            <option value="wa" {if $_c['user_notification_payment']=='wa' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp')}</option>
                            <option value="sms" {if $_c['user_notification_payment']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="email" {if $_c['user_notification_payment']=='email' }selected="selected"
                                {/if}>{Lang::T('By Email')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('User will get invoice notification when buy package or package refilled')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Reminder Notification')}</label>
                    <div class="col-md-5">
                        <select name="user_notification_reminder" id="user_notification_reminder" class="form-control">
                            <option value="none">{Lang::T('None')}</option>
                            <option value="wa" {if $_c['user_notification_reminder']=='wa' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp')}</option>
                            <option value="sms" {if $_c['user_notification_reminder']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="sms" {if $_c['user_notification_reminder']=='email' }selected="selected"
                                {/if}>{Lang::T('By Email')}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Reminder Notify Intervals')}</label><br>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="notification_reminder_1day" value="yes"
                            {if !isset($_c['notification_reminder_1day']) || $_c['notification_reminder_1day'] neq 'no'}checked{/if}>
                        {Lang::T('1 Day')}
                    </label>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="notification_reminder_3days" value="yes"
                            {if !isset($_c['notification_reminder_3days']) || $_c['notification_reminder_3days'] neq 'no'}checked{/if}>
                        {Lang::T('3 Days')}
                    </label>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="notification_reminder_7days" value="yes"
                            {if !isset($_c['notification_reminder_7days']) || $_c['notification_reminder_7days'] neq 'no'}checked{/if}>
                        {Lang::T('7 Days')}
                    </label>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Expiry Edit Notification')}</label>
                    <div class="col-md-5">
                        <select name="notification_expiry_edit" id="notification_expiry_edit" class="form-control">
                            <option value="no" {if isset($_c['notification_expiry_edit']) && $_c['notification_expiry_edit']=='no'}selected="selected"{/if}>{Lang::T('No')}</option>
                            <option value="yes" {if !isset($_c['notification_expiry_edit']) || $_c['notification_expiry_edit']!='no'}selected="selected"{/if}>{Lang::T('Yes')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Send Expiry Edit Notification template when extend action succeeds')}</p>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="TawkToChatWidget">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseTawkToChatWidget" aria-expanded="false" aria-controls="collapseTawkToChatWidget">
                    {Lang::T('Tawk.to Chat Widget')}
                </a>
            </h4>
        </div>
        <div id="collapseTawkToChatWidget" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">https://tawk.to/chat/</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="tawkto" name="tawkto" value="{$_c['tawkto']}"
                            placeholder="62f1ca7037898912e961f5/1ga07df">
                    </div>
                    <p class="help-block col-md-4">{Lang::T('From Direct Chat Link.')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">Tawk.to Javascript API key</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="tawkto_api_key" name="tawkto_api_key"
                            value="{$_c['tawkto_api_key']}" placeholder="39e52264cxxxxxxxxxxxxxdd078af5342e8">
                    </div>
                </div>
                <label class="col-md-2"></label>
                <p class="col-md-5 help-block">/ip hotspot walled-garden<br>
                    add dst-host=tawk.to<br>
                    add dst-host=*.tawk.to</p>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="APIKey">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseAPIKey" aria-expanded="false" aria-controls="collapseAPIKey">
                    API Key
                </a>
            </h4>
        </div>
        <div id="collapseAPIKey" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Access Token')}</label>
                    <div class="col-md-5">
                        <input type="password" class="form-control" id="api_key" name="api_key" value="{$_c['api_key']}"
                            placeholder="{Lang::T('Empty this to randomly created API key')}"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('This Token will act as SuperAdmin/Admin')}</p>
                </div>
                <hr>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('API Rate Limit')}</label>
                    <div class="col-md-5">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="api_rate_limit_enabled" value="1"
                                {if !isset($_c['api_rate_limit_enabled']) || $_c['api_rate_limit_enabled'] neq 'no'}checked{/if}>
                            {Lang::T('Enabled')}
                        </label>
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Applies to all API requests')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Rate Limit Max')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="api_rate_limit_max" name="api_rate_limit_max"
                            value="{$_c['api_rate_limit_max']|default:120}" min="0" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Requests per window (0 = unlimited)')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Rate Limit Window')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="api_rate_limit_window" name="api_rate_limit_window"
                            value="{$_c['api_rate_limit_window']|default:60}" min="0" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Window in seconds')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('API Key Attempts Max')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="admin_api_key_attempts_max" name="admin_api_key_attempts_max"
                            value="{$_c['admin_api_key_attempts_max']|default:5}" min="1" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Allowed failures before backoff')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Attempts Window')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="admin_api_key_attempts_window" name="admin_api_key_attempts_window"
                            value="{$_c['admin_api_key_attempts_window']|default:300}" min="60" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Seconds to count failures')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('API Key Backoff')}</label>
                    <div class="col-md-5">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="admin_api_key_backoff_enabled" value="1"
                                {if !isset($_c['admin_api_key_backoff_enabled']) || $_c['admin_api_key_backoff_enabled'] neq 'no'}checked{/if}>
                            {Lang::T('Enabled')}
                        </label>
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Throttle invalid API key attempts')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Backoff Base Delay')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="admin_api_key_backoff_base_delay"
                            name="admin_api_key_backoff_base_delay" value="{$_c['admin_api_key_backoff_base_delay']|default:5}" min="0" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Seconds (first wait)')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Backoff Max Delay')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="admin_api_key_backoff_max_delay"
                            name="admin_api_key_backoff_max_delay" value="{$_c['admin_api_key_backoff_max_delay']|default:3600}" min="0" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Seconds (maximum wait)')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Backoff Reset Window')}</label>
                    <div class="col-md-5">
                        <input type="number" class="form-control" id="admin_api_key_backoff_reset_window"
                            name="admin_api_key_backoff_reset_window" value="{$_c['admin_api_key_backoff_reset_window']|default:900}" min="0" step="1">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('Seconds without attempts to reset')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('API Key Allowlist')}</label>
                    <div class="col-md-5">
                        <textarea class="form-control" id="admin_api_key_allowlist" name="admin_api_key_allowlist"
                            rows="3" placeholder="127.0.0.1&#10;192.168.1.0/24">{$_c['admin_api_key_allowlist']|escape}</textarea>
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('One IP/CIDR per line')}</p>
                </div>
                <div class="form-group" id="api-key-blocks">
                    <label class="col-md-3 control-label">{Lang::T('Blocked IPs')}</label>
                    <div class="col-md-9">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-sm-4">
                                <input type="text" class="form-control input-sm" name="api_block_add_ip"
                                    placeholder="{Lang::T('IP')}" autocomplete="off">
                            </div>
                            <div class="col-sm-5">
                                <input type="datetime-local" class="form-control input-sm" name="api_block_add_blocked_until"
                                    value="{$api_block_default_until|escape}">
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-xs btn-success" formnovalidate
                                    formaction="{Text::url('')}settings/api-block-add"
                                    data-toggle="tooltip" title="{Lang::T('Add')}">
                                    <span class="fa fa-plus"></span>
                                </button>
                            </div>
                        </div>

                        {if isset($api_key_blocks) && $api_key_blocks|@count > 0}
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{Lang::T('IP')}</th>
                                            <th>{Lang::T('Blocked Until')}</th>
                                            <th>{Lang::T('Failures')}</th>
                                            <th>{Lang::T('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach $api_key_blocks as $block}
                                            <tr>
                                                <td>{$block.ip|escape}</td>
                                                <td>
                                                    {if isset($api_block_edit_ip) && $api_block_edit_ip eq $block.ip}
                                                        <input type="datetime-local" class="form-control input-sm" name="api_block_edit_blocked_until"
                                                            value="{$block.blocked_until_input|escape}">
                                                    {else}
                                                        {$block.blocked_until_human|escape}
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if isset($api_block_edit_ip) && $api_block_edit_ip eq $block.ip}
                                                        <input type="number" class="form-control input-sm" name="api_block_edit_fail_count"
                                                            value="{$block.fail_count|escape}" min="0" step="1">
                                                    {else}
                                                        {$block.fail_count|escape}
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if isset($api_block_edit_ip) && $api_block_edit_ip eq $block.ip}
                                                        <input type="hidden" name="api_block_edit_ip" value="{$block.ip|escape}">
                                                        <button type="submit" class="btn btn-xs btn-primary" formnovalidate
                                                            formaction="{Text::url('')}settings/api-block-edit"
                                                            data-toggle="tooltip" title="{Lang::T('Save')}">
                                                            <span class="fa fa-check"></span>
                                                        </button>
                                                        <a class="btn btn-xs btn-default"
                                                            href="{Text::url('settings/app')}#api-key-blocks"
                                                            data-toggle="tooltip" title="{Lang::T('Cancel')}">
                                                            <span class="fa fa-times"></span>
                                                        </a>
                                                    {else}
                                                        <a class="btn btn-xs btn-default"
                                                            href="{Text::url('settings/app&api_block_edit=')}{ $block.ip|escape:'url' }#api-key-blocks"
                                                            data-toggle="tooltip" title="{Lang::T('Edit')}">
                                                            <span class="fa fa-pencil"></span>
                                                        </a>
                                                        <a class="btn btn-xs btn-danger"
                                                            href="{Text::url('settings/api-unblock&ip=')}{ $block.ip|escape:'url' }&csrf_token={$csrf_token}#api-key-blocks"
                                                            onclick="return ask(this, '{Lang::T('Unblock this IP?')}');"
                                                            data-toggle="tooltip" title="{Lang::T('Unblock')}">
                                                            <span class="fa fa-unlock"></span>
                                                        </a>
                                                    {/if}
                                                </td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        {else}
                            <p class="help-block">{Lang::T('No blocked IPs')}</p>
                        {/if}
                    </div>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Proxy">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseProxy"
                    aria-expanded="false" aria-controls="collapseProxy">
                    {Lang::T('Proxy')}
                </a>
            </h4>
        </div>
        <div id="collapseProxy" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Proxy Server')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="http_proxy" name="http_proxy"
                            value="{$_c['http_proxy']}" placeholder="127.0.0.1:3128">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Proxy Server Login')}</label>
                    <div class="col-md-5">
                        <input type="password" class="form-control" id="http_proxyauth" name="http_proxyauth"
                            autocomplete="off" value="{$_c['http_proxyauth']}" placeholder="username:password"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                    </div>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="TaxSystem">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseTaxSystem" aria-expanded="false" aria-controls="collapseTaxSystem">
                    {Lang::T('Tax System')}
                </a>
            </h4>
        </div>
        <div id="collapseTaxSystem" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Tax System')}</label>
                    <div class="col-md-5">
                        <select name="enable_tax" id="enable_tax" class="form-control">
                            <option value="no" {if $_c['enable_tax']=='no' }selected="selected" {/if}>
                                {Lang::T('No')}
                            </option>
                            <option value="yes" {if $_c['enable_tax']=='yes' }selected="selected" {/if}>
                                {Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Tax will be calculated in Internet Plan Price')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Tax Rate')}</label>
                    <div class="col-md-5">
                        <select name="tax_rate" id="tax_rate" class="form-control">
                            <option value="0.5" {if $_c['tax_rate']=='0.5' }selected="selected" {/if}>
                                0.5
                            </option>
                            <option value="1" {if $_c['tax_rate']=='1' }selected="selected" {/if}>
                                1
                            </option>
                            <option value="1.5" {if $_c['tax_rate']=='1.5' }selected="selected" {/if}>
                                1.5
                            </option>
                            <option value="2" {if $_c['tax_rate']=='2' }selected="selected" {/if}>
                                2
                            </option>
                            <option value="5" {if $_c['tax_rate']=='5' }selected="selected" {/if}>
                                5
                            </option>
                            <option value="10" {if $_c['tax_rate']=='10' }selected="selected" {/if}>
                                10
                            </option>
                            <!-- Custom tax rate option -->
                            <option value="custom" {if $_c['tax_rate']=='custom' }selected="selected" {/if}>
                                {Lang::T('Custome')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Tax Rates by percentage')}</p>
                </div>
                <!-- Custom tax rate input field (initially hidden) -->
                <div class="form-group" id="customTaxRate" style="display: none;">
                    <label class="col-md-3 control-label">{Lang::T('Custome Tax Rate')}</label>
                    <div class="col-md-5">
                        <input type="text" value="{$_c['custom_tax_rate']}" class="form-control" name="custom_tax_rate"
                            id="custom_tax_rate" placeholder="{Lang::T('Enter Custome Tax Rate')}">
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Enter the custom tax rate (e.g., 3.75 for 3.75%)')}</p>
                </div>

                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="GithubAuthentication">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseAuthentication" aria-expanded="false" aria-controls="collapseAuthentication">
                    Github {Lang::T('Authentication')}
                </a>
            </h4>
        </div>
        <div id="collapseAuthentication" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Github Username')}</label>
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-addon">https://github.com/</span>
                            <input type="text" class="form-control" id="github_username" name="github_username"
                                value="{$_c['github_username']}" placeholder="ibnux">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Github Token')}</label>
                    <div class="col-md-5">
                        <input type="password" class="form-control" id="github_token" name="github_token"
                            value="{$_c['github_token']}" placeholder="ghp_........"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                    </div>
                    <span class="help-block col-md-4"><a href="https://github.com/settings/tokens/new"
                            target="_blank">{Lang::T('Create GitHub personal access token')} (classic)</a>,
                        {Lang::T('only need repo
                        scope')}</span>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-offset-2 col-md-8" style="text-align: left;">{Lang::T('This
                        will allow
                        you to download plugin from private/paid repository')}</label>
                </div>
                <button class="btn btn-success btn-block js-settings-submit" type="button">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>
</form>
<div class="bs-callout bs-callout-info" id="callout-navbar-role">
    <h4><b>{Lang::T('Settings For Mikrotik')}</b></h4>
    <p>/ip hotspot walled-garden <br>
        add dst-host={$_domain} <br>
        add dst-host=*.{$_domain}
    </p>
    <br>
    <h4><b>{Lang::T('Settings For Cron Expired')}</b></h4>
    <p>
        # {Lang::T('Expired Cronjob Every 5 Minutes [Recommended]')}<br>
        */5 * * * * cd {$dir} && {$php} cron.php
        <br><br>
        # {Lang::T('Expired Cronjob Every 1 Hour')}<br>
        0 * * * * cd {$dir} && {$php} cron.php
    </p>
    <br>
    <h4><b>{Lang::T('Settings For Cron Reminder')}</b></h4>
    <p>
        # {Lang::T('Reminder Cronjob Every 7 AM')}<br>
        0 7 * * * cd {$dir} && {$php} cron_reminder.php
    </p>
</div>

<script>
    function testWa() {
        var target = prompt("Phone number\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{Text::url('settings/app&testWa=')}' + target;
        }
    }

    function testSms() {
        var target = prompt("Phone number\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{Text::url('settings/app&testSms=')}' + target;
        }
    }


    function testEmail() {
        var target = prompt("Email\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{Text::url('settings/app&testEmail=')}' + target;
        }
    }

    function testTg() {
        window.location.href = '{Text::url('settings/app&testTg=test')}';
    }

    function toggleWhatsappAuthFields() {
        var authSelect = document.getElementById('wa_gateway_auth_type');
        if (!authSelect) {
            return;
        }
        var authType = (authSelect.value || 'none').toLowerCase();
        var authFields = document.querySelectorAll('#wa_gateway_post_fields .wa-auth-field');
        authFields.forEach(function(field) {
            var allowed = (field.getAttribute('data-auth') || '').toLowerCase().split(' ');
            var show = authType !== 'none' && allowed.indexOf(authType) !== -1;
            field.style.display = show ? '' : 'none';
            field.querySelectorAll('input,select,textarea').forEach(function(input) {
                input.disabled = !show;
            });
        });
    }

    function toggleWhatsappGatewayFields() {
        var methodSelect = document.getElementById('wa_gateway_method');
        var postFields = document.getElementById('wa_gateway_post_fields');
        var getFields = document.getElementById('wa_gateway_get_fields');
        if (!methodSelect || !postFields || !getFields) {
            return;
        }
        var method = (methodSelect.value || 'post').toLowerCase();
        var showPost = method !== 'get';
        postFields.style.display = showPost ? '' : 'none';
        getFields.style.display = showPost ? 'none' : '';

        postFields.querySelectorAll('input,select,textarea').forEach(function(input) {
            input.disabled = !showPost;
        });
        getFields.querySelectorAll('input,select,textarea').forEach(function(input) {
            input.disabled = showPost;
        });

        if (showPost) {
            toggleWhatsappAuthFields();
        }
    }
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        var methodSelect = document.getElementById('wa_gateway_method');
        var authSelect = document.getElementById('wa_gateway_auth_type');
        if (methodSelect) {
            methodSelect.addEventListener('change', toggleWhatsappGatewayFields);
        }
        if (authSelect) {
            authSelect.addEventListener('change', toggleWhatsappAuthFields);
        }
        toggleWhatsappGatewayFields();

        var sectionTimeoutCheckbox = document.getElementById('enable_session_timeout');
        var timeoutDurationInput = document.getElementById('timeout_duration_input');
        var timeoutDurationField = document.getElementById('session_timeout_duration');
        var turnstileAdmin = document.getElementById('turnstile_admin_enabled');
        var turnstileClient = document.getElementById('turnstile_client_enabled');
        var turnstileSiteKey = document.querySelector('input[name="turnstile_site_key"]');
        var genieAcsEnable = document.getElementById('genieacs_enable');
        var genieAcsUrlWrap = document.getElementById('genieacs_url_wrap');
        var genieAcsUrl = document.getElementById('genieacs_url');

        function toggleGenieAcsUrlRequirement() {
            if (!genieAcsEnable || !genieAcsUrlWrap || !genieAcsUrl) {
                return;
            }
            var enabled = genieAcsEnable.value === 'yes';
            genieAcsUrlWrap.style.display = enabled ? '' : 'none';
            genieAcsUrl.required = enabled;
            if (!enabled) {
                genieAcsUrl.value = genieAcsUrl.value.trim();
            }
        }

        function requireTurnstileSiteKey() {
            if (!turnstileSiteKey || !turnstileAdmin || !turnstileClient) {
                return;
            }
            var anyEnabled = turnstileAdmin.value === '1' || turnstileClient.value === '1';
            turnstileSiteKey.required = anyEnabled;
        }

        requireTurnstileSiteKey();
        if (turnstileAdmin) {
            turnstileAdmin.addEventListener('change', requireTurnstileSiteKey);
        }
        if (turnstileClient) {
            turnstileClient.addEventListener('change', requireTurnstileSiteKey);
        }
        toggleGenieAcsUrlRequirement();
        if (genieAcsEnable) {
            genieAcsEnable.addEventListener('change', toggleGenieAcsUrlRequirement);
        }

	        if (sectionTimeoutCheckbox && timeoutDurationInput && timeoutDurationField) {
	            if (sectionTimeoutCheckbox.checked) {
	                timeoutDurationInput.style.display = 'block';
	                timeoutDurationField.required = true;
	            }

            sectionTimeoutCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    timeoutDurationInput.style.display = 'block';
                    timeoutDurationField.required = true;
                } else {
                    timeoutDurationInput.style.display = 'none';
                    timeoutDurationField.required = false;
                }
            });

	            var form = document.querySelector('form');
	            if (form) {
	                form.addEventListener('submit', function(event) {
	                    // This page has multiple submit buttons that override the action via
	                    // `formaction` (e.g. Blocked IPs CRUD). Only validate when saving the
	                    // main settings form (settings/app-post).
	                    var submitter = event.submitter || document.activeElement;
	                    var targetAction = form.getAttribute('action') || '';
	                    if (submitter && submitter.getAttribute) {
	                        var fa = submitter.getAttribute('formaction');
	                        if (fa) {
	                            targetAction = fa;
	                        }
	                    }
	                    if (targetAction.indexOf('settings/app-post') === -1) {
	                        return;
	                    }
	                    if (sectionTimeoutCheckbox.checked && (!timeoutDurationField.value || isNaN(
	                            timeoutDurationField.value))) {
	                        event.preventDefault();
	                        alert('Please enter a valid session timeout duration.');
	                        timeoutDurationField.focus();
	                    }
	                });
		            }
		        }

	        var regWelcomeSelect = document.getElementById('reg_send_welcome_message');
	        var regWelcomeViaGroup = document.getElementById('reg_welcome_via_group');
	        function toggleRegWelcomeVia() {
	            if (!regWelcomeSelect || !regWelcomeViaGroup) {
	                return;
	            }
	            var enabled = (regWelcomeSelect.value === 'yes');
	            regWelcomeViaGroup.style.display = enabled ? '' : 'none';
	        }
	        if (regWelcomeSelect) {
	            regWelcomeSelect.addEventListener('change', toggleRegWelcomeVia);
	        }
	        toggleRegWelcomeVia();
	    });
	</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Function to toggle visibility of custom tax rate input field
        function toggleCustomTaxRate() {
            var taxRateSelect = document.getElementById("tax_rate");
            var customTaxRateInput = document.getElementById("customTaxRate");

            if (taxRateSelect.value === "custom") {
                customTaxRateInput.style.display = "block";
            } else {
                customTaxRateInput.style.display = "none";
            }
        }

        // Call the function when the page loads
        toggleCustomTaxRate();

        // Call the function whenever the tax rate dropdown value changes
        document.getElementById("tax_rate").addEventListener("change", toggleCustomTaxRate);
    });
</script>
<script>
    document.getElementById('login_page_type').addEventListener('change', function() {
        var selectedValue = this.value;
        var customFields = document.getElementById('customFields');

        if (selectedValue === 'custom') {
            customFields.style.display = 'block';
        } else {
            customFields.style.display = 'none';
        }
    });
    document.getElementById('login_page_type').dispatchEvent(new Event('change'));
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var form = document.getElementById('settings_app_form');
        if (!form) return;
        var buttons = document.querySelectorAll('.js-settings-submit');
        buttons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                form.submit();
            });
        });
    });
</script>
{include file="sections/footer.tpl"}
