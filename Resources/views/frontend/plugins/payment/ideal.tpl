{namespace name='frontend/plugins/payment/ideal'}

<h4>{s name="PluginsIdealLabel" namespace="frontend/paynl/plugins"}{/s}</h4>

<div class="select-field">
    <select id="issuer-select" name="issuer">
        <option value="0">{s name="PluginsIdealSelect" namespace="frontend/paynl/plugins"}{/s}</option>
        {foreach from=$issuers item=issuer}
            <option value="{$issuer->id}"{if $selectedIssuer == $issuer->id} selected{/if}>{$issuer->name}</option>
        {/foreach}
    </select>
</div>
