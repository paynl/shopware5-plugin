<h4>Please select iDEAL issuer</h4>

<div class="select-field">
    <select id="issuer-select" name="issuer">
        {foreach from=$issuers item=issuer}
            <option>Select your bank</option>
            <option value="{$issuer->id}"{if $selectedIssuer == $issuer->id} selected{/if}>{$issuer->name}</option>
        {/foreach}
    </select>
</div>
