{include file="sections/header.tpl"}
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #333;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .form-group select {
        margin-left: 10px;
        margin-right: 10px;
    }

    .page-item {
        width: 100px;
        display: block;
        height: 34px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;

    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save"
                        href="{Text::url('customers/csv&token=', $csrf_token)}" onclick="return ask(this, '{Lang::T("
                        This will export to CSV")}?')"><span class="glyphicon glyphicon-download"
                            aria-hidden="true"></span> CSV</a>
                </div>
                {/if}
                {Lang::T('Manage Contact')}
            </div>
            <div class="panel-body">
                <form id="site-search" method="post" action="{Text::url('customers')}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                        <div class="col-lg-4">
                            <div class="input-group">
                                <span class="input-group-addon">{Lang::T('Order ')}&nbsp;&nbsp;</span>
                                <div class="row row-no-gutters">
                                    <div class="col-xs-8">
                                        <select class="form-control" id="order" name="order">
                                            <option value="username" {if $order eq 'username' }selected{/if}>
                                                {Lang::T('Username')}</option>
                                            <option value="fullname" {if $order eq 'fullname' }selected{/if}>
                                                {Lang::T('First Name')}</option>
                                            <option value="lastname" {if $order eq 'lastname' }selected{/if}>
                                                {Lang::T('Last Name')}</option>
                                            <option value="created_at" {if $order eq 'created_at' }selected{/if}>
                                                {Lang::T('Created Date')}</option>
                                            <option value="balance" {if $order eq 'balance' }selected{/if}>
                                                {Lang::T('Balance')}</option>
                                            <option value="status" {if $order eq 'status' }selected{/if}>
                                                {Lang::T('Status')}</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control" id="orderby" name="orderby">
                                            <option value="asc" {if $orderby eq 'asc' }selected{/if}>
                                                {Lang::T('Ascending')}</option>
                                            <option value="desc" {if $orderby eq 'desc' }selected{/if}>
                                                {Lang::T('Descending')}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="input-group">
                                <span class="input-group-addon">{Lang::T('Status')}</span>
                                <select class="form-control" id="filter" name="filter">
                                    {foreach $statuses as $status}
                                    <option value="{$status}" {if $filter eq $status }selected{/if}>{Lang::T($status)}
                                    </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control"
                                    placeholder="{Lang::T('Search')}..." value="{$search}">
                                <div class="input-group-btn">
                                    <button class="btn btn-primary" type="submit"><span class="fa fa-search"></span>
                                        {Lang::T('Search')}</button>
                                    <button class="btn btn-info" type="submit" name="export" value="csv">
                                        <span class="glyphicon glyphicon-download" aria-hidden="true"></span> CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <a href="{Text::url('customers/add')}" class="btn btn-success text-black btn-block"
                                title="{Lang::T('Add')}">
                                <i class="ion ion-android-add"></i><i class="glyphicon glyphicon-user"></i>
                            </a>
                        </div>
                    </div>
                </form>
                <br>&nbsp;
                <div class="table-responsive table_mobile">
                    <div class="form-group">
                        <span>Show</span>
                        <select class="page-item" id="per_page" name="per_page" onchange="changePerPage(this)">
                            <option value="10" {if $cookie eq 10}selected{/if}>10</option>
                            <option value="25" {if $cookie eq 25}selected{/if}>25</option>
                            <option value="50" {if $cookie eq 50}selected{/if}>50</option>
                            <option value="100" {if $cookie eq 100}selected{/if}>100</option>
                            <option value="200" {if $cookie eq 200}selected{/if}>200</option>
                            <option value="500" {if $cookie eq 500}selected{/if}>500</option>
                            <option value="1000" {if $cookie eq 1000}selected{/if}>1000</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <table id="customerTable" class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th>{Lang::T('Username')}</th>
                                <th>Photo</th>
                                <th>{Lang::T('Account Type')}</th>
                                <th>{Lang::T('Full Name')}</th>
                                <th>{Lang::T('Balance')}</th>
                                <th>{Lang::T('Contact')}</th>
                                <th>{Lang::T('Package')}</th>
                                <th>{Lang::T('Service Type')}</th>
                                <th>PPPOE</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Created On')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                            <tr {if $ds['status'] !='Active' }class="danger" {/if}>
                                <td><input type="checkbox" name="customer_ids[]" value="{$ds['id']}"></td>
                                <td onclick="window.location.href = '{Text::url('customers/view/', $ds['id'])}'"
                                    style="cursor:pointer;">{$ds['username']}</td>
                                <td>
                                    <a href="{$app_url}/{$UPLOAD_PATH}{$ds['photo']}" target="photo">
                                        {assign var='rowPhotoPath' value=$ds['photo']}
                                        {if !$rowPhotoPath || strstr($rowPhotoPath, 'default')}
                                            {assign var='rowPhotoSrc' value=$app_url|cat:'/'|cat:$UPLOAD_PATH|cat:'/user.default.jpg'}
                                        {else}
                                            {assign var='cleanRowPhoto' value=$rowPhotoPath|trim:'/'}
                                            {assign var='rowPhotoSrc' value=$app_url|cat:'/'|cat:$UPLOAD_PATH|cat:'/'|cat:$cleanRowPhoto|cat:'.thumb.jpg'}
                                        {/if}
                                        <img src="{$rowPhotoSrc}" width="32" alt="">
                                    </a>
                                </td>
                                <td>{$ds['account_type']}</td>
                                <td onclick="window.location.href = '{Text::url('customers/view/', $ds['id'])}'"
                                    style="cursor: pointer;">{$ds['fullname']}</td>
                                <td>{Lang::moneyFormat($ds['balance'])}</td>
                                <td align="center">
                                    {if $ds['phonenumber']}
                                    <a href="tel:{$ds['phonenumber']}" class="btn btn-default btn-xs"
                                        title="{$ds['phonenumber']}"><i class="glyphicon glyphicon-earphone"></i></a>
                                    {/if}
                                    {if $ds['email']}
                                    <a href="mailto:{$ds['email']}" class="btn btn-default btn-xs"
                                        title="{$ds['email']}"><i class="glyphicon glyphicon-envelope"></i></a>
                                    {/if}
                                    {if $ds['coordinates']}
                                    <a href="https://www.google.com/maps/dir//{$ds['coordinates']}/" target="_blank"
                                        class="btn btn-default btn-xs" title="{$ds['coordinates']}"><i
                                            class="glyphicon glyphicon-map-marker"></i></a>
                                    {/if}
                                </td>
                                <td align="center" api-get-text="{Text::url('autoload/plan_is_active/')}{$ds['id']}">
                                    <span class="label label-default">&bull;</span>
                                </td>
                                <td>{$ds['service_type']}</td>
                                <td>
                                    {$ds['pppoe_username']}
                                    {if !empty($ds['pppoe_username']) && !empty($ds['pppoe_ip'])}:{/if}
                                    {$ds['pppoe_ip']}
                                </td>
                                <td>{Lang::T($ds['status'])}</td>
                                <td>{Lang::dateTimeFormat($ds['created_at'])}</td>
                                <td align="center">
                                    <a href="{Text::url('customers/view/')}{$ds['id']}" id="{$ds['id']}"
                                        style="margin: 0px; color:black"
                                        class="btn btn-success btn-xs">&nbsp;&nbsp;{Lang::T('View')}&nbsp;&nbsp;</a>
                                    <a href="{Text::url('customers/edit/', $ds['id'])}"
                                        id="{$ds['id']}" style="margin: 0px; color:black"
                                        class="btn btn-info btn-xs">&nbsp;&nbsp;{Lang::T('Edit')}&nbsp;&nbsp;</a>
                                    <form method="post" action="{Text::url('customers/sync/', $ds['id'])}"
                                        style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <button type="submit" id="{$ds['id']}" style="margin: 5px; color:black"
                                            class="btn btn-success btn-xs">&nbsp;&nbsp;{Lang::T('Sync')}&nbsp;&nbsp;</button>
                                    </form>
                                    <a href="{Text::url('plan/recharge/', $ds['id'])}" id="{$ds['id']}" style="margin: 0px;"
                                        class="btn btn-primary btn-xs">{Lang::T('Recharge')}</a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    <div class="row" style="padding: 5px">
                        <div class="col-lg-3 col-lg-offset-9">
                            <div class="btn-group btn-group-justified" role="group">
                                <!-- <div class="btn-group" role="group">
                                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                    <button id="deleteSelectedTokens" class="btn btn-danger">{Lang::T('Delete
                                        Selected')}</button>
                                    {/if}
                                </div> -->
                                <div class="btn-group" role="group">
                                    <button id="sendMessageToSelected" class="btn btn-success">{Lang::T('Send
                                        Message')}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {include file="pagination.tpl"}
            </div>
        </div>
    </div>
</div>
<!-- Modal for Sending Messages -->
<div id="sendMessageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="sendMessageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendMessageModalLabel">{Lang::T('Send Message')}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <select style="margin-bottom: 10px;" id="messageType" class="form-control">
                    <option value="all">{Lang::T('All')}</option>
                    <option value="email">{Lang::T('Email')}</option>
                    <option value="inbox">{Lang::T('Inbox')}</option>
                    <option value="sms">{Lang::T('SMS')}</option>
                    <option value="wa">{Lang::T('WhatsApp')}</option>
                </select>
                <input type="text" style="margin-bottom: 10px;" class="form-control" id="subject-content" value=""
                    placeholder="{Lang::T('Enter message subject here')}">
                <textarea id="messageContent" class="form-control" rows="4"
                    placeholder="{Lang::T('Enter your message here...')}"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{Lang::T('Close')}</button>
                <button type="button" id="sendMessageButton" class="btn btn-primary">{Lang::T('Send Message')}</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Select or deselect all checkboxes
    document.getElementById('select-all').addEventListener('change', function () {
        var checkboxes = document.querySelectorAll('input[name="customer_ids[]"]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    $(document).ready(function () {
        let selectedCustomerIds = [];

        // Collect selected customer IDs when the button is clicked
        $('#sendMessageToSelected').on('click', function () {
            selectedCustomerIds = $('input[name="customer_ids[]"]:checked').map(function () {
                return $(this).val();
            }).get();

            if (selectedCustomerIds.length === 0) {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please select at least one customer to send a message.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Open the modal
            $('#sendMessageModal').modal('show');
        });

        // Handle sending the message
        $('#sendMessageButton').on('click', function () {
            const message = $('#messageContent').val().trim();
            const messageType = $('#messageType').val();
            const subject = $('#subject-content').val().trim();


            if (!message) {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please enter a message to send.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (messageType == 'all' || messageType == 'inbox' || messageType == 'email' && !subject) {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please enter a subject for the message.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Disable the button and show loading text
            $(this).prop('disabled', true).text('{Lang::T('Sending...')}');

            $.ajax({
                url: '?_route=message/send_bulk_selected',
                method: 'POST',
                data: {
                    customer_ids: selectedCustomerIds,
                    message_type: messageType,
                    message: message
                },
                dataType: 'json',
                success: function (response) {
                    // Handle success response
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: "{Lang::T('Message sent successfully.')}",
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: "{Lang::T('Error sending message: ')}" + response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                    $('#sendMessageModal').modal('hide');
                    $('#messageContent').val(''); // Clear the message content
                },
                error: function () {
                    Swal.fire({
                        title: 'Error!',
                        text: "{Lang::T('Failed to send the message. Please try again.')}",
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function () {
                    // Re-enable the button and reset text
                    $('#sendMessageButton').prop('disabled', false).text('{Lang::T('Send Message')}');
                }
            });
        });
    });

    $(document).ready(function () {
        $('#sendMessageModal').on('show.bs.modal', function () {
            $(this).attr('inert', 'true');
        });
        $('#sendMessageModal').on('shown.bs.modal', function () {
            $('#messageContent').focus();
            $(this).removeAttr('inert');
        });
        $('#sendMessageModal').on('hidden.bs.modal', function () {
            // $('#button').focus();
        });
    });
</script>
<script>
    document.getElementById('messageType').addEventListener('change', function () {
        const messageType = this.value;
        const subjectField = document.getElementById('subject-content');

        subjectField.style.display = (messageType === 'all' || messageType === 'email' || messageType === 'inbox') ? 'block' : 'none';

        switch (messageType) {
            case 'all':
                subjectField.placeholder = 'Enter a subject for all channels';
                subjectField.required = true;
                break;
            case 'email':
                subjectField.placeholder = 'Enter a subject for email';
                subjectField.required = true;
                break;
            case 'inbox':
                subjectField.placeholder = 'Enter a subject for inbox';
                subjectField.required = true;
                break;
            default:
                subjectField.placeholder = 'Enter message subject here';
                subjectField.required = false;
                break;
        }
    });

    function changePerPage(select) {
        setCookie('customer_per_page', select.value, 365);
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
</script>
{include file = "sections/footer.tpl" }