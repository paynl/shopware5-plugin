{extends file="frontend/index/index.tpl"}
{block name='frontend_index_content_left'}{/block}
{block name='frontend_index_breadcrumb'}
    <div style="padding:20px">
        <div class="alert is--info is--rounded">
            <div class="alert--icon">
                <!-- Alert message icon -->
                <i class="icon--element icon--check"></i>
            </div>
            <div class="alert--content">
                {$message}
            </div>
        </div>
        <br><br>
        Change payment method / Zahlungsmethode ändern <a class="btn" href="/checkout/shippingPayment/sTarget/checkout">Okay</a>
    </div>
{/block}