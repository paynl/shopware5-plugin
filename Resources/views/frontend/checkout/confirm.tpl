{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_left_payment_method'}
    <p class="payment--method-info">
        <strong class="payment--title">{s name="ConfirmInfoPaymentMethod" namespace="frontend/checkout/confirm"}{/s}</strong>
        <span class="payment--description">{$sUserData.additional.payment.description}</span>
        {if $bankData}
            <span class="payment--description">( {$bankData->name} )</span>
        {/if}
    </p>

    {if !$sUserData.additional.payment.esdactive && {config name="showEsd"}}
        <p class="payment--confirm-esd">{s name="ConfirmInfoInstantDownload" namespace="frontend/checkout/confirm"}{/s}</p>
    {/if}
{/block}

