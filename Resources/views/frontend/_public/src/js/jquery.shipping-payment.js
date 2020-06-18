$.subscribe('plugin/swShippingPayment/onInputChanged', function() {
    StateManager.updatePlugin($('*[data-datepicker="true"]'), 'swDatePicker');
});
