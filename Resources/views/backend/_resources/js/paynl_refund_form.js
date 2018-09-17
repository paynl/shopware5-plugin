function updateRefundAmount(){
    var total = 0;

    $('.select-refund-qty').each(function (i,selectBox) {
        var price = $(selectBox).attr('data-price');
        var qty = $(selectBox).val();
        total += price*qty;
        console.log(selectBox);
        console.log(price+' x '+qty+' = '+price*qty);
    });

    total += $('#checkRefundShipping').is(':checked')?$('#checkRefundShipping:checked').attr('data-price')*1:0;

    $('#refundAmount').val(total.toFixed(2));
}



$('.select-refund-qty').change(updateRefundAmount);
$('#checkRefundShipping').change(updateRefundAmount);

updateRefundAmount();

