{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    <div class="panel--body is--wide block-group">
        {foreach $sPayments as $payment_mean}
            {if $payment_mean.action == constant("PaynlPayment\PaynlPayment::PLUGIN_NAME")}
                <div class="payment--method paynl-payment-method block{if $payment_mean@last} method_last{else} method{/if}">
                    <div class="paynl-radio-block display-inline-middle">

                        {* Radio Button *}
                        {block name='frontend_checkout_payment_fieldset_input_radio'}
                            <div class="method--input paynl-method--input">
                                <input type="radio" name="payment" class="radio auto_submit display-inline-middle" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}"{if $payment_mean.id eq $sFormData.payment or (!$sFormData && !$smarty.foreach.register_payment_mean.index)} checked="checked"{/if} />
                            </div>
                        {/block}

                        {* Method Name *}
                        {block name='frontend_checkout_payment_fieldset_input_label'}
                            <div class="method--label is--first mb-xs-5">
                                <label class="method--name is--strong"
                                       for="payment_mean{$payment_mean.id}">
                                    {if $payment_mean.class}
                                        <div class="display-inline-middle">
                                            <img src="{link file='frontend/_resources/logos/%s.png'|sprintf:{$payment_mean.class}}" />
                                        </div>
                                    {/if}
                                    <div class="display-inline-middle paynl-payment-method-name">
                                        {$payment_mean.description}
                                    </div>
                                </label>
                            </div>
                        {/block}
                    </div>
                    <div class="min-md-display-inline-middle paynl-payment-template">
                        {if "frontend/plugins/payment/`$payment_mean.template`"|template_exists}
                            <div class="method--bankdata{if $payment_mean.id != $form_data.payment} is--hidden{/if}">
                                {include file="frontend/plugins/payment/`$payment_mean.template`" form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPayments}
                            </div>
                        {/if}
                    </div>

                    {* Method Description *}
                    {block name='frontend_checkout_payment_fieldset_description'}
                        <div class="method--description paynl-method--description is--last">
                            {include file="string:{$payment_mean.additionaldescription}"}
                        </div>
                    {/block}

                    {* Method Logo *}
                    {block name='frontend_checkout_payment_fieldset_template'}
                        <div class="payment--method-logo payment_logo_{$payment_mean.name}"></div>
                    {/block}
                </div>
            {else}
                <div class="payment--method block{if $payment_mean@last} method_last{else} method{/if}">

                    {* Radio Button *}
                    {block name='frontend_checkout_payment_fieldset_input_radio'}
                        <div class="method--input">
                            <input type="radio" name="payment" class="radio auto_submit" value="{$payment_mean.id}"
                                   id="payment_mean{$payment_mean.id}"{if $payment_mean.id eq $sFormData.payment or (!$sFormData && !$smarty.foreach.register_payment_mean.index)} checked="checked"{/if} />
                        </div>
                    {/block}

                    {* Method Name *}
                    {block name='frontend_checkout_payment_fieldset_input_label'}
                        <div class="method--label is--first">
                            <label class="method--name is--strong"
                                   for="payment_mean{$payment_mean.id}">{$payment_mean.description}</label>
                        </div>
                    {/block}

                    {* Method Description *}
                    {block name='frontend_checkout_payment_fieldset_description'}
                        <div class="method--description is--last">
                            {include file="string:{$payment_mean.additionaldescription}"}
                        </div>
                    {/block}

                    {* Method Logo *}
                    {block name='frontend_checkout_payment_fieldset_template'}
                        <div class="payment--method-logo payment_logo_{$payment_mean.name}"></div>
                        {if "frontend/plugins/payment/`$payment_mean.template`"|template_exists}
                            <div class="method--bankdata{if $payment_mean.id != $form_data.payment} is--hidden{/if}">
                                {include file="frontend/plugins/payment/`$payment_mean.template`" form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPayments}
                            </div>
                        {/if}
                    {/block}
                </div>
            {/if}
        {/foreach}
    </div>
{/block}

