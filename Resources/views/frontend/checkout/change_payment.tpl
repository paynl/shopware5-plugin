{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    <div class="panel--body is--wide block-group">
        {foreach $sPayments as $payment_mean}
            {if $payment_mean.action == constant("PaynlPayment\PaynlPayment::PLUGIN_NAME")}
                <div class="payment--method paynl-payment-method block{if $payment_mean@last} method_last{else} method{/if}">
                    <div class="paynl-radio-block {if $payment_mean['name'] == 'paynl_10'}paynl-ideal-radio-block {/if}display-inline-middle">
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
                                {if !empty($payment_mean.additionaldescription)}
                                    <div class="paynl-paynl-payment-tooltip paynl-paynl-payment-tooltip-logo {$showDescription}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                            <path fill="#758CA3" fill-rule="evenodd" d="M12,7 C12.5522847,7 13,7.44771525 13,8 C13,8.55228475 12.5522847,9 12,9 C11.4477153,9 11,8.55228475 11,8 C11,7.44771525 11.4477153,7 12,7 Z M13,16 C13,16.5522847 12.5522847,17 12,17 C11.4477153,17 11,16.5522847 11,16 L11,11 C11,10.4477153 11.4477153,10 12,10 C12.5522847,10 13,10.4477153 13,11 L13,16 Z M24,12 C24,18.627417 18.627417,24 12,24 C5.372583,24 6.14069502e-15,18.627417 5.32907052e-15,12 C-8.11624501e-16,5.372583 5.372583,4.77015075e-15 12,3.55271368e-15 C18.627417,5.58919772e-16 24,5.372583 24,12 Z M12,2 C6.4771525,2 2,6.4771525 2,12 C2,17.5228475 6.4771525,22 12,22 C17.5228475,22 22,17.5228475 22,12 C22,6.4771525 17.5228475,2 12,2 Z"></path>
                                        </svg>
                                        <span class="tooltiptext">{$payment_mean.additionaldescription}</span>
                                    </div>
                                {/if}
                            </div>
                        {/block}
                    </div>
                    <div class="min-md-display-inline-middle {if $payment_mean['name'] == 'paynl_10'}paynl-ideal-payment-template {/if}paynl-payment-template">
                        {if "frontend/plugins/payment/`$payment_mean.template`"|template_exists}
                            <div class="method--bankdata{if $payment_mean.id != $form_data.payment} is--hidden{/if} paynl-method--bankdata">
                                {include file="frontend/plugins/payment/`$payment_mean.template`" form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPayments}
                            </div>
                        {/if}
                    </div>

                    {* Method Description *}
                    {block name='frontend_checkout_payment_fieldset_description'}
                        {if !empty($payment_mean.additionaldescription)}
                            <div class="method--description paynl-method--description {if $payment_mean['name'] == 'paynl_10'}paynl-ideal-method--description {/if}is--last  {$showDescription}">
                                <div class="paynl-paynl-payment-tooltip">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <path fill="#758CA3" fill-rule="evenodd" d="M12,7 C12.5522847,7 13,7.44771525 13,8 C13,8.55228475 12.5522847,9 12,9 C11.4477153,9 11,8.55228475 11,8 C11,7.44771525 11.4477153,7 12,7 Z M13,16 C13,16.5522847 12.5522847,17 12,17 C11.4477153,17 11,16.5522847 11,16 L11,11 C11,10.4477153 11.4477153,10 12,10 C12.5522847,10 13,10.4477153 13,11 L13,16 Z M24,12 C24,18.627417 18.627417,24 12,24 C5.372583,24 6.14069502e-15,18.627417 5.32907052e-15,12 C-8.11624501e-16,5.372583 5.372583,4.77015075e-15 12,3.55271368e-15 C18.627417,5.58919772e-16 24,5.372583 24,12 Z M12,2 C6.4771525,2 2,6.4771525 2,12 C2,17.5228475 6.4771525,22 12,22 C17.5228475,22 22,17.5228475 22,12 C22,6.4771525 17.5228475,2 12,2 Z"></path>
                                    </svg>
                                    <span class="tooltiptext">{$payment_mean.additionaldescription}</span>
                                </div>
                            </div>
                        {/if}
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

