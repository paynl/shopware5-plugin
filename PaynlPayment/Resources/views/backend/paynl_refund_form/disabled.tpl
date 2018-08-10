{extends file="parent:backend/_layout/layout.tpl"}
{block name="content/main"}
    <div class="alert alert-danger" role="alert">
        {s namespace="backend/paynl/refund" name="message/disabled"}Refund is disabled, you can enable this in the pay.nl plugin settings{/s}
    </div>
{/block}