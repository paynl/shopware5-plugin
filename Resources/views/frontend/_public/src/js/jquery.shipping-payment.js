$.subscribe('plugin/swShippingPayment/onInputChanged', function() {
    let checkedRadio = $('.payment--method-list .radio:checked')[0];
    let checkedDescription = $(checkedRadio).closest('.payment--method').find('.method--description').text();
    let paymentName = $(checkedRadio).closest('.payment--method').find('.method--name').text();
    $('.table--footer .is--last .benefit--text').text(paymentName + ":\n" + checkedDescription);
});
