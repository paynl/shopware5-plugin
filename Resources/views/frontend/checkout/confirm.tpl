{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_error_messages' append}
    {if $isCancelled}
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content">
                {s name="PaymentWasCancelled" namespace="frontend/paynl/plugins"}{/s}
            </div>
        </div>
    {/if}
{/block}

