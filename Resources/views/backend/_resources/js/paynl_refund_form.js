function updateRefundAmount() {
    var total = 0;
    var selectsArray = document.getElementsByClassName("select-refund-qty");

    for (var i = 0; i < selectsArray.length; i++) {
        var priceAmount = selectsArray[i].dataset.price;
        var qtyAmount = selectsArray[i].value;

        total += priceAmount * qtyAmount;
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