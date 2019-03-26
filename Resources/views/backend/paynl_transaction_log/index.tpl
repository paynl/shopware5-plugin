{extends file="parent:backend/_layout/layout.tpl"}
{block name="content/main"}
    {if $statusChanges}
        {foreach $statusChanges as $statusChange}
            <h5> {$statusChange['createdAt']} {$statusChange['statusBefore']} -> {$statusChange['statusAfter']}</h5>
            <table class="table">
                <tr>
                    <th>Product</th>
                    <th>Voorraad voor</th>
                    <th>Voorraad na</th>
                </tr>
                {foreach $statusChange['products'] as $product}
                    <tr>
                        <td>{$product['name']}</td>
                        <td class="{($product['stockBefore']<0)?'text-danger':''}">{$product['stockBefore']}</td>
                        <td class="{($product['stockAfter']<0)?'text-danger':''}">{$product['stockAfter']}</td>
                    </tr>
                {/foreach}
            </table>
        {/foreach}
    {else}
        <h2>No logs found</h2>
    {/if}
{/block}