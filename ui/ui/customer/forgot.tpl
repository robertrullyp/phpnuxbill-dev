{include file="customer/header-public.tpl"}

<div class="hidden-xs" style="height:100px"></div>
<form action="{Text::url('forgot&step=')}{$step+1}" method="post">
    <div class="row">
        <div class="col-sm-4 col-sm-offset-4">
            {if $step == 1}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Verification Code')}</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="input-group">
                                {if $_c['registration_username'] == 'phone'}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                {elseif $_c['registration_username'] == 'email'}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                                {else}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" readonly class="form-control" name="username" value="{$username}"
                                    placeholder="{if $_c['registration_username'] == 'phone'}{if $_c['country_code_phone'] != ''}{$_c['country_code_phone']} {/if}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-asterisk"></i></span>
                                <input type="text" required class="form-control" id="otp_code"
                                    placeholder="{Lang::T('Verification Code')}" name="otp_code">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-block btn-primary">{Lang::T('Validate')}</button>
                        <a href="{Text::url('forgot&step=-1')}" class="btn btn-block btn-link">{Lang::T('Cancel')}</a>
                    </div>
                </div>
            {elseif $step == 2}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Success')}</div>
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
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                {elseif $_c['registration_username'] == 'email'}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                                {else}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" readonly class="form-control" name="username" value="{$username}"
                                    placeholder="{if $_c['registration_username'] == 'phone'}{if $_c['country_code_phone'] != ''}{$_c['country_code_phone']} {/if}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}">
                            </div>
                        </div>
                        <label>{Lang::T('Your Password has been change to')}</label>
                        <input type="text" readonly class="form-control" value="{$passsword}" onclick="this.select()">
                        <p class="help-block">
                            {Lang::T('Use the password to login, and change the password from password change page')}</p>
                    </div>
                    <div class="panel-footer">
                        <a href="{Text::url('login')}" class="btn btn-block btn-primary">{Lang::T('Back')}</a>
                    </div>
                </div>
            {elseif $step == 6}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Forgot Username')}</div>
                    <div class="panel-body">
                        {if $_c['registration_username'] == 'email'}
                            <label>{Lang::T('Please input your Phone Number')}</label>
                            <input type="text" name="find" class="form-control" required value=""
                                placeholder="{if $_c['country_code_phone'] != ''}{$_c['country_code_phone']} {/if}{Lang::T('Phone Number')}">
                        {elseif $_c['registration_username'] == 'phone'}
                            <label>{Lang::T('Please input your Email')}</label>
                            <input type="text" name="find" class="form-control" required value=""
                                placeholder="{Lang::T('Email')}">
                        {else}
                            <label>{Lang::T('Please input your Email or Phone number')}</label>
                            <input type="text" name="find" class="form-control" required value=""
                                placeholder="{Lang::T('Email or Phone number')}">
                        {/if}
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-block btn-primary">{Lang::T('Validate')}</button>
                        <a href="{Text::url('forgot')}" class="btn btn-block btn-link">{Lang::T('Back')}</a>
                    </div>
                </div>
            {else}
                <div class="panel panel-primary">
                    <div class="panel-heading">{Lang::T('Forgot Password')}</div>
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
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                {elseif $_c['registration_username'] == 'email'}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                                {else}
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                {/if}
                                <input type="text" class="form-control" name="username" required
                                    placeholder="{if $_c['registration_username'] == 'phone'}{if $_c['country_code_phone'] != ''}{$_c['country_code_phone']} {/if}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-block btn-primary">{Lang::T('Validate')}</button>
                        <a href="{Text::url('forgot&step=6')}"
                            class="btn btn-block btn-link">{Lang::T('Forgot Usernames')}</a>
                        <a href="{Text::url('login')}" class="btn btn-block btn-link">{Lang::T('Back')}</a>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</form>
{include file="customer/footer-public.tpl"}