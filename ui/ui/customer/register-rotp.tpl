{include file="customer/header-public.tpl"}

<div class="hidden-xs" style="height:100px"></div>

<div class="row">
    <div class="col-md-2">
    </div>
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">{Lang::T('Registration Info')}</div>
            <div class="panel-body">
                {include file="$_path/../pages/Registration_Info.html"}
            </div>
        </div>
    </div>
    <form action="{Text::url('register')}" method="post">
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">1. {Lang::T('Register as Member')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>
                            {if $_c['registration_username'] == 'phone'}
                                {Lang::T('Phone Number')}
                            {elseif $_c['registration_username'] == 'email'}
                                {Lang::T('Email')}
                            {else}
                                {Lang::T('Usernames')}
                            {/if}
                        </label>
                        <div class="input-group">
                            {if $_c['registration_username'] == 'phone'}
                                <span class="input-group-addon" id="basic-addon1"><i class="glyphicon glyphicon-phone-alt"></i></span>
                            {elseif $_c['registration_username'] == 'email'}
                                <span class="input-group-addon" id="basic-addon1"><i class="glyphicon glyphicon-envelope"></i></span>
                            {else}
                                <span class="input-group-addon" id="basic-addon1"><i class="glyphicon glyphicon-user"></i></span>
                            {/if}
                            <input type="text" class="form-control" name="phone_number"
                                placeholder="{if $_c['registration_username'] == 'phone'}{if $_c['country_code_phone'] != ''}{$_c['country_code_phone']} {/if}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}"
                                inputmode="numeric" pattern="[0-9]*">
                        </div>
                    </div>
                    <div class="btn-group btn-group-justified mb15">
                        <div class="btn-group">
                            <a href="{Text::url('login')}" class="btn btn-warning">{Lang::T('Cancel')}</a>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-success" type="submit">{Lang::T('Request OTP')}</button>
                        </div>
                    </div>
                    <br>
                    <center>
                        <a href="javascript:showPrivacy()">Privacy</a>
                        &bull;
                        <a href="javascript:showTaC()">T &amp; C</a>
                    </center>
                </div>
            </div>
        </div>
    </form>
</div>

{include file="customer/footer-public.tpl"}