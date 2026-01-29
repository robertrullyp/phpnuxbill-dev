{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Edit Service Package')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('services/balance-edit-post')}">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-10">
                            <label class="radio-inline warning">
                                <input type="radio" checked name="enabled" value="1"> {Lang::T('Enable')}
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="enabled" value="0"> {Lang::T('Disable')}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Package Name')}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="name" value="{$d['name_plan']}"
                                name="name" maxlength="40" placeholder="{Lang::T('Topup')} 100">
                        </div>
                    </div>
                    <div class="form-group has-success">
                        <label class="col-md-2 control-label">{Lang::T('Package Price')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon">{$_c['currency_code']}</span>
                                <input type="number" class="form-control" name="price" value="{$d['price']}" required>
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
                        <label class="col-md-2 control-label">{Lang::T('Visibility')}</label>
                        <div class="col-md-10">
                            <label class="radio-inline"><input type="radio" name="visibility" value="all" {if $d['visibility'] == 'all' || !$d['visibility']}checked{/if}> {Lang::T('semua pelanggan')}</label>
                            <label class="radio-inline"><input type="radio" name="visibility" value="exclude" {if $d['visibility'] == 'exclude'}checked{/if}> {Lang::T('semua kecuali yang ada di custom list')}</label>
                            <label class="radio-inline"><input type="radio" name="visibility" value="custom" {if $d['visibility'] == 'custom'}checked{/if}> {Lang::T('hanya yang ada di custom list')}</label>
                        </div>
                    </div>
                    <div class="form-group" id="visibility_customers" style="display:none;">
                        <label class="col-md-2 control-label">{Lang::T('Customer List')}</label>
                        <div class="col-md-6">
                            <select id="visible_customers" name="visible_customers[]" class="form-control select2" multiple>
                                {if isset($visible_customer_options)}
                                    {foreach $visible_customer_options as $vc}
                                        <option value="{$vc['id']}" selected>{$vc['fullname']} - {$vc['username']} - {$vc['email']}</option>
                                    {/foreach}
                                {/if}
                            </select>
                            <p class="help-block">{Lang::T('Search by Full Name, Username, Phone or Email')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder Notification')}</label>
                        <div class="col-md-10">
                            <input type="hidden" name="reminder_enabled" value="0">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="reminder_enabled" value="1" {if !isset($d['reminder_enabled']) || $d['reminder_enabled'] != 0}checked{/if}>
                                {Lang::T('Send reminder notifications for this plan')}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Invoice Notification')}</label>
                        <div class="col-md-10">
                            <input type="hidden" name="invoice_notification" value="0">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="invoice_notification" value="1" {if !isset($d['invoice_notification']) || $d['invoice_notification'] != 0}checked{/if}>
                                {Lang::T('Send invoice notifications for this plan')}
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
                    <div class="form-group has-warning">
                        <label class="col-md-2 control-label">{Lang::T('Price Before Discount')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon">{$_c['currency_code']}</span>
                                <input type="number" class="form-control" name="price_old" required value="{$d['price_old']}">
                            </div>
                            <p class="help-block">{Lang::T('For Discount Rate, this is price before get discount, must be more expensive with real price')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success" onclick="return ask(this, '{Lang::T("Continue the process of changing the balance contents?")}')" type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{Text::url('services/balance')}">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
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
