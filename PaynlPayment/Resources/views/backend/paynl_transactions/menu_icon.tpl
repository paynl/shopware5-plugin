{block name="backend/base/header/css"}
    {$smarty.block.parent}
    <style type="text/css">
        .sprite--paynl-logo {
            background: url({link file="backend/_resources/img/paynl-logo.png"}) no-repeat 0 0 !important;
        }
        .paynl-hidden{
            display: none !important;
        }
    </style>
{/block}