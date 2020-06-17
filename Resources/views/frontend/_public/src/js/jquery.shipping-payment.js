$.overridePlugin('swShippingPayment', {
    onInputChanged: function () {
        var me = this,
            form = me.$el.find(me.opts.formSelector),
            url = form.attr('action'),
            data = form.serialize() + '&isXHR=1';

        $.publish('plugin/swShippingPayment/onInputChangedBefore', [ me ]);

        $.loadingIndicator.open();

        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            success: function(res) {
                me.$el.empty().html(res);
                me.$el.find('input[type="submit"][form], button[form]').swFormPolyfill();
                $.loadingIndicator.close();
                window.picturefill();

                StateManager.updatePlugin($('*[data-datepicker="true"]'), 'swDatePicker');
                $.publish('plugin/swShippingPayment/onInputChanged', [ me ]);
            }
        });
    }
});
