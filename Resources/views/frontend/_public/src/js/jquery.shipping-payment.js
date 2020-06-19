$.subscribe('plugin/swShippingPayment/onInputChanged', function() {
    StateManager.updatePlugin($('*[data-datepicker="true"]'), 'swDatePicker');

    let checkedRadio = $('.payment--method-list .radio:checked')[0];
    let checkedDescription = $(checkedRadio).closest('.payment--method').find('.method--description').text();
    let paymentName = $(checkedRadio).closest('.payment--method').find('.method--name').text();
    $('.table--footer .is--last .benefit--text').text(paymentName + ":\n" + checkedDescription);
});
