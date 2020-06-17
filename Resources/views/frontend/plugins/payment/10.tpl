{namespace name='frontend/plugins/payment'}

{if $paynlIssuers}
    <div class="select-field">
        <select id="issuer-select" name="paynlIssuer">
            <option value="0">{s name="PluginsIdealSelect" namespace="frontend/paynl/plugins"}{/s}</option>
            {foreach from=$paynlIssuers item=issuer}
                <option value="{$issuer->id}"{if $paynlSelectedIssuer == $issuer->id} selected{/if}>{$issuer->name}</option>
            {/foreach}
        </select>
    </div>
{/if}
