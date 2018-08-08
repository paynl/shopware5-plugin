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
                <div>
                    <strong>Customer: </strong>
                    <span>{$customerName}</span>
                </div>

                <div>
                    <strong>OrderNumber:</strong>
                    <span>{$orderNumber}</span>
                </div>
                <div>
                    <strong>Amount:</strong>
                    <span>{$currencySymbol} {$orderAmount|number_format:2:",":"."}</span>
                </div>
                {if $currencyFactor != 1}
                <div>
                    <strong>Currency factor:</strong>
                    <span> {$currencyFactor}</span>
                </div>
                {/if}
                <div>
                    <strong>Amount refunded:</strong>
                    <span>&euro; {$refundedAmount|number_format:2:",":"."}</span>
                </div>

                <div>
                    <strong>Available for refund:</strong>
                    <span>&euro; {($availableForRefund)|number_format:2:",":"."}</span>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="refundAmount">Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">&euro;</span>
                        </div>
                        <input name="amount" step=".01" max="{$availableForRefund}" type="number" class="form-control" id="refundAmount">
                    </div>

                </div>
                <div class="form-group">
                    <label for="refundDescription">Description</label>
                    <input name="description" type="text" class="form-control" id="refundDescription">
                </div>
                <div class="form-group">
                    <input class="btn btn-primary" type="submit" value="Refund">
                </div>
            </div>
        </div>


        <table class="table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Refund</th>
                <th>Restock</th>
            </tr>
            </thead>
            <tbody>
            {foreach $details as $detail}
                <tr>
                    <td>{$detail['name']}</td>
                    <td>{$currencySymbol} {$detail['price']|number_format:2:",":"."}</td>
                    <td>{$detail['quantity']}</td>
                    <td>
                        <select data-price="{$detail['price']/$currencyFactor}" class="form-control select-refund-qty"
                                name="product[{$detail['id']}][qty]" id="">
                            {for $i=0;$i<=$detail['quantity'];$i++}
                                <option value="{$i}">{$i}</option>
                            {/for}
                        </select>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input position-static" type="checkbox"
                                   name="product[{$detail['id']}][restock]" value="1" aria-label="...">
                        </div>
                    </td>
                </tr>
            {/foreach}
            {if $shippingAmount != 0}
                <tr>
                    <td>Shipping</td>
                    <td>{$currencySymbol} {$shippingAmount|number_format:2:",":"."}</td>
                    <td colspan="3">
                        <div class="form-check">
                            <input data-price="{$shippingAmount/$currencyFactor}" id="checkRefundShipping" type="checkbox"
                                   class="form-check-input">
                            Refund shipping costs</label>
                        </div>
                    </td>
                </tr>
            {/if}
            </tbody>
        </table>

    </form>
{/block}