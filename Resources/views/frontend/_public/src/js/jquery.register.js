$.overridePlugin('swRegister', {
    onPaymentChanged: function () {
        var me = this;

        me.superclass.onPaymentChanged.apply(this, arguments);

        if ($('#issuer-select')[0] !== $(arguments[0].originalEvent.target)[0]) {
            $('#issuer-select').val(0);
        }
    }
});
