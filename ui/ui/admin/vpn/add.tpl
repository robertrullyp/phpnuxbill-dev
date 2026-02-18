{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Add Service Plan')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}services/vpn-add-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Customer cannot buy disabled Package, but admin can recharge it, use it if you want only admin recharge it")}">?</a>
                        </label>
                        <div class="col-md-10">
                            <input type="radio" checked name="enabled" value="1"> {Lang::T('Enable')}
                            <input type="radio" name="enabled" value="0"> {Lang::T('Disable')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Postpaid will have fix expired date")}">?</a>
                        </label>
                        <div class="col-md-10">
                            <input type="radio" name="prepaid" onclick="prePaid()" value="yes" checked> {Lang::T('Prepaid')}
                            <input type="radio" name="prepaid" onclick="postPaid()" value="no"> {Lang::T('Postpaid')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Type')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Personal Package will only show to personal Customer, Business Package will only show to Business Customer")}">?</a>
                        </label>
                        <div class="col-md-10">
                            <input type="radio" name="plan_type" value="Personal" checked> {Lang::T('Personal')}
                            <input type="radio" name="plan_type" value="Business"> {Lang::T('Business')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Visibility')}</label>
                        <div class="col-md-10">
                            <label class="radio-inline"><input type="radio" name="visibility" value="all" {if $last_visibility == 'all'}checked{/if}> {Lang::T('All')}</label>
                            <label class="radio-inline"><input type="radio" name="visibility" value="exclude" {if $last_visibility == 'exclude'}checked{/if}> {Lang::T('Exclude')}</label>
                            <label class="radio-inline"><input type="radio" name="visibility" value="custom" {if $last_visibility == 'custom'}checked{/if}> {Lang::T('Include')}</label>
                        </div>
                    </div>
                    <div class="form-group" id="visibility_customers" style="display:none;">
                        <label class="col-md-2 control-label">{Lang::T('Allowed/Excluded Customers')}</label>
                        <div class="col-md-6">
                            <select id="visible_customers" name="visible_customers[]" class="form-control select2" multiple></select>
                            <p class="help-block">{Lang::T('Search by Full Name, Username, Phone or Email')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder Notification')}</label>
                        <div class="col-md-10">
                            <input type="hidden" name="reminder_enabled" value="0">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="reminder_enabled" value="1" checked>
                                {Lang::T('Send reminder notifications for this plan')}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Invoice Notification')}</label>
                        <div class="col-md-10">
                            <input type="hidden" name="invoice_notification" value="0">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="invoice_notification" value="1" checked>
                                {Lang::T('Send invoice notifications for this plan')}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Customer Self Extend')}</label>
                        <div class="col-md-10">
                            <input type="hidden" name="customer_can_extend" value="0">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="customer_can_extend" value="1" checked>
                                {Lang::T('Allow customer self-extend for this plan')}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Linked Plans')}</label>
                        <div class="col-md-6">
                            <select name="linked_plans[]" class="form-control select2" multiple>
                                {foreach $plan_options as $plan}
                                    <option value="{$plan.id}" {if isset($selected_linked_plans) && in_array($plan.id, $selected_linked_plans)}selected{/if}>
                                        {$plan.name_plan} ({$plan.type})
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">{Lang::T('Linked Plans Help')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Device')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("This Device are the logic how PHPNuxBill Communicate with Mikrotik or other Devices")}">?</a>
                        </label>
                        <div class="col-md-6">
                            <select class="form-control" id="device" name="device">
                                {foreach $devices as $dev}
                                    <option value="{$dev}" {if $dev == 'MikrotikVpn'}selected{/if}>{$dev}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="name_plan" maxlength="40" name="name_plan">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{Text::url('')}bandwidth/add">{Lang::T('Bandwidth Name')}</a></label>
                        <div class="col-md-6">
                            <select id="id_bw" name="id_bw" class="form-control select2">
                                <option value="">{Lang::T('Select Bandwidth')}...</option>
                                {foreach $d as $ds}
                                    <option value="{$ds['id']}">{$ds['name_bw']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Price')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon">{$_c['currency_code']}</span>
                                <input type="number" class="form-control" name="price" required>
                            </div>
                        </div>
                        {if $_c['enable_tax'] == 'yes'}
                            {if $_c['tax_rate'] == 'custom'}
                                <p class="help-block col-md-4">{number_format($_c['custom_tax_rate'], 2)} % {Lang::T('Tax Rates
                            will be added')}</p>
                            {else}
                                <p class="help-block col-md-4">{number_format($_c['tax_rate'] * 100, 2)} % {Lang::T('Tax Rates
                            will be added')}</p>
                            {/if}
                        {/if}
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Validity')}</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="validity" name="validity">
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="validity_unit" name="validity_unit">
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('1 Period = 1 Month, Expires the 20th of each month')}
                        </p>
                    </div>
                    <div class="form-group hidden" id="expired_date">
                        <label class="col-md-2 control-label">{Lang::T('Expired Date')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Expired will be this date every month")}">?</a>
                        </label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="expired_date" maxlength="2" value="20" min="1" max="28" step="1" >
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{Text::url('')}routers/add">{Lang::T('Router Name')}</a></label>
                        <div class="col-md-6">
                            <select id="routers" name="routers" required class="form-control select2">
                                <option value=''>{Lang::T('Select Routers')}</option>
                                {foreach $r as $rs}
                                    <option value="{$rs['name']}">{$rs['name']}</option>
                                {/foreach}
                            </select>
                            <p class="help-block">{Lang::T('Cannot be change after saved')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a href="{Text::url('')}pool/add">{Lang::T('IP Pool')}</a></label>
                        <div class="col-md-6">
                            <select id="pool_name" name="pool_name" required class="form-control select2">
                                <option value=''>{Lang::T('Select Pool')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-10">
                            <button class="btn btn-primary" onclick="return ask(this, '{Lang::T("Continue the VPN creation process?")}')" type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{Text::url('')}services/pppoe">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    var preOpt = `<option value="Mins">{Lang::T('Mins')}</option>
    <option value="Hrs">{Lang::T('Hrs')}</option>
    <option value="Days">{Lang::T('Days')}</option>
    <option value="Months">{Lang::T('Months')}</option>`;
    var postOpt = `<option value="Period">{Lang::T('Period')}</option>`;
    function prePaid() {
        $("#validity_unit").html(preOpt);
        $('#expired_date').addClass('hidden');
    }

    function postPaid() {
        $("#validity_unit").html(postOpt);
        $("#expired_date").removeClass('hidden');
    }
    document.addEventListener("DOMContentLoaded", function(event) {
        prePaid()
    })
</script>
<script>
    function toggleVisibilitySelector() {
        var val = document.querySelector('input[name="visibility"]:checked').value;
        document.getElementById('visibility_customers').style.display = (val === 'custom' || val === 'exclude') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', function(){
        toggleVisibilitySelector();
        document.querySelectorAll('input[name="visibility"]').forEach(function(el){ el.addEventListener('change', toggleVisibilitySelector); });
        $('#visible_customers').select2({
            theme: 'bootstrap',
            ajax: {
                url: function (params) {
                    if (params.term != undefined) {
                        return '{Text::url('autoload/customer_select2')}&s=' + params.term;
                    } else {
                        return '{Text::url('autoload/customer_select2')}';
                    }
                },
                dataType: 'json', delay: 250, processResults: function (data) { return data; }, cache: true
            }
        });
    });
</script>
{include file="sections/footer.tpl"}
