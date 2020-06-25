function updateRefundAmount() {
    let total = 0;
    let selectsArray = document.getElementsByClassName("select-refund-qty");

    for (let i = 0; i < selectsArray.length; i++) {
        let priceAmount = selectsArray[i].dataset.price;
        let quantityAmount = selectsArray[i].value;

        total += priceAmount * quantityAmount;
    }

    let refundShippingEl = document.getElementById("checkRefundShipping");
    if (refundShippingEl) {
        let isShipingAmountRefunded = refundShippingEl.checked;
        if (isShipingAmountRefunded) {
            total += refundShippingEl.dataset.price * 1;
        }
    }

    document.getElementById('refundAmount').value = total.toFixed(2);
}

let selects = document.getElementsByClassName("select-refund-qty");
for (let index = 0; index < selects.length; index++) {
    selects[index].onchange = updateRefundAmount;
}

let checkRefundShipping = document.getElementById('checkRefundShipping');
if (checkRefundShipping) {
    checkRefundShipping.onchange = updateRefundAmount;
}

updateRefundAmount();
