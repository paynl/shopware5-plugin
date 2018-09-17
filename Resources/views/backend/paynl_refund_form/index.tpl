{extends file="parent:backend/_layout/layout.tpl"}
{block name="content/javascript"}
    <script type="text/javascript" src="{link file="backend/_resources/js/paynl_refund_form.js"}"></script>
{/block}
{block name="content/main"}
    {if $messages}
        {foreach $messages as $message}
            <div class="alert alert-{$message['type']}" role="alert">
                {$message['content']}
            </div>
        {/foreach}
    {/if}
    <form action="{link file='backend/PaynlRefundForm/refund'}" method="post">
        <input type="hidden" name="paynlPaymentId" value="{$paynlPaymentId}">
        <div class="row">
            <div class="col-6">

                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/order/main" name="column/amount"}Amount{/s}
                    </div>
                    <div class="col-6 ">
                        {$currencySymbol} {$orderAmount|number_format:2:",":"."}
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/order/main" name="column/customer"}Customer{/s}
                    </div>
                    <div class="col-6 ">
                        {$customerName}
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/order/main" name="column/number"}Ordernumber{/s}
                    </div>
                    <div class="col-6 ">
                        {$orderNumber}
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/order/main" name="column/transaction"}Transactions{/s}
                    </div>
                    <div class="col-6 ">
                        {$transactionId}
                    </div>
                </div>

                <div class="row">
                    <div class="col-12"><hr /></div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/paynl/refund" name="column/transaction_id"}Pay.nl Transaction id{/s}
                    </div>
                    <div class="col-6 ">
                        {$paynlOrderId}
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/paynl/refund" name="column/paid_amount"}Paid amount{/s}
                    </div>
                    <div class="col-6 ">
                        {$currencySymbol} {$paidCurrencyAmount|number_format:2:",":"."}
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/paynl/refund" name="column/refunded_amount"}Refunded amount{/s}
                    </div>
                    <div class="col-6 ">
                        {$currencySymbol} {$refundedCurrencyAmount|number_format:2:",":"."}
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 font-weight-bold">
                        {s namespace="backend/paynl/refund" name="column/refund_available"}Available for refund{/s}
                    </div>
                    <div class="col-6 ">
                        {$currencySymbol} {($paidCurrencyAmount-$refundedCurrencyAmount)|number_format:2:",":"."}
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="refundAmount">{s namespace="backend/order/main" name="column/amount"}Amount{/s}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">{$currencySymbol}</span>
                        </div>
                        <input name="amount" step=".01" type="number" class="form-control" id="refundAmount">
                    </div>

                </div>
                <div class="form-group">
                    <label for="refundDescription">Description</label>
                    <input name="description" type="text" class="form-control" id="refundDescription">
                </div>
                <div class="form-group">
                    <input class="btn btn-primary" type="submit" value="{s namespace="backend/paynl/refund" name="column/refund"}Refund{/s}">
                </div>
            </div>
        </div>


        <table class="table">
            <thead>
            <tr>
                <th>{s namespace="backend/paynl/refund" name="column/product"}Product{/s}</th>
                <th>{s namespace="backend/paynl/refund" name="column/price"}Price{/s}</th>
                <th>{s namespace="backend/paynl/refund" name="column/quantity"}Quantity{/s}</th>
                <th>{s namespace="backend/paynl/refund" name="column/refund"}Refund{/s}</th>
                <th>{s namespace="backend/paynl/refund" name="column/restock"}Restock{/s}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $details as $detail}
                <tr>
                    <td>{$detail['name']}</td>
                    <td>{$currencySymbol} {$detail['price']|number_format:2:",":"."}</td>
                    <td>{$detail['quantity']}</td>
                    <td>
                        <select data-price="{$detail['price']}" class="form-control select-refund-qty"
                                name="product[{$detail['id']}][qty]" id="">
                            {for $i=0;$i<=$detail['quantity'];$i++}
                                <option value="{$i}">{$i}</option>
                            {/for}
                        </select>
                    </td>
                    <td>
                        {if $detail['id'] != 0}
                        <div class="form-check">
                            <input class="form-check-input position-static" type="checkbox"
                                   name="product[{$detail['id']}][restock]" value="1" aria-label="{s namespace="backend/paynl/refund" name="column/restock"}Restock{/s}">
                        </div>
                        {/if}
                    </td>
                </tr>
            {/foreach}
            {if $shippingAmount != 0}
                <tr>
                    <td>{s namespace="backend/paynl/refund" name="column/shipping"}Shipping{/s}</td>
                    <td>{$currencySymbol} {$shippingAmount|number_format:2:",":"."}</td>
                    <td colspan="3">
                        <div class="form-check">
                            <input data-price="{$shippingAmount}" id="checkRefundShipping" type="checkbox"
                                   class="form-check-input">
                            {s namespace="backend/paynl/refund" name="text/refund_shipping"}Refund shipping cost{/s}</label>
                        </div>
                    </td>
                </tr>
            {/if}
            </tbody>
        </table>

    </form>
{/block}