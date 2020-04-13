function updateRefundAmount(){
    var total = 0;
    var selects = document.getElementsByClassName("select-refund-qty");
    for (var index = 0; index < selects.length; index++) {
        var price = selects[index].dataset.price;
        var qty = selects[index].value;

        total += price * qty;
    }

    var isShipingAmountRefunded = document.getElementById("checkRefundShipping").checked;
    if (isShipingAmountRefunded) {
        var refundShippingEl = document.getElementById('checkRefundShipping');
        total += refundShippingEl.dataset.price * 1;
    }

    document.getElementById('refundAmount').value = total.toFixed(2);
}

var selects = document.getElementsByClassName("select-refund-qty");
for (var index = 0; index < selects.length; index++) {
    selects[index].onchange = updateRefundAmount;
}

document.getElementById('checkRefundShipping').onchange = updateRefundAmount;

updateRefundAmount();

