<option value="">{Lang::T('Select PPPoE Service')}</option>
{if $error neq ''}
    <option value="" disabled>{Lang::T('Unable to load PPPoE service list')} - {$error|escape}</option>
{/if}
{if $d|@count == 0 && $error eq ''}
    <option value="" disabled>{Lang::T('No PPPoE service available on router')}</option>
{/if}
{foreach $d as $serviceName name=pppoe_services}
    <option value="{$serviceName|escape}" {if $selected eq $serviceName || ($selected eq '' && $smarty.foreach.pppoe_services.first)}selected{/if}>{$serviceName|escape}</option>
{/foreach}
