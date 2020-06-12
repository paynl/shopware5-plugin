{extends file="parent:frontend/account/index.tpl"}

{block name="frontend_account_index_payment_method_content"}
    <div class="panel--body is--wide">
        <p>
            <strong>{$sUserData.additional.payment.description}</strong> {if $bankData} ( {$bankData->name} ){/if}<br />

            {if !$sUserData.additional.payment.esdactive && {config name="showEsd"}}
                {s name="AccountInfoInstantDownloads"}{/s}
            {/if}
        </p>
    </div>
{/block}
