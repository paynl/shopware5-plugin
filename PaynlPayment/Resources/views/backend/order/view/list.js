//{block name="backend/order/view/list/list" append}
Ext.override(Shopware.apps.Order.view.list.List, {

    createActionColumn: function () {
        var me = this;

        var buttons = me.callParent(arguments);
        console.log('args',arguments);
        buttons.items.push(me.getPaynlButton());
        buttons.width += 30;

        return buttons;
    },
    getPaynlButton: function () {
        var me = this;

        return {
            action: 'notice',
            tooltip: '{s name="tooltip/paynl"}Open in Pay.nl{/s}',
            iconCls: 'sprite--paynl-logo',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                console.log(record);

                var url = "https://admin.pay.nl/transactions/details/" + record.get('temporaryId');
                window.open(url, 'paynl_info', 'toolbar=no,' +
                    ' location=no,' +
                    ' status=no,' +
                    ' menubar=no,' +
                    ' scrollbars=yes,' +
                    ' resizable=yes,' +
                    ' width=800,' +
                    ' height=800');
            },
            getClass: function(value, metadata, record) {
                console.log('getClass doet t');
                if(
                    me.hasOrderPaymentName(record) &&
                    me.getOrderPaymentName(record).substring(0, 6) === 'paynl_'
                ) {
                    return '';
                }
                return 'paynl-hidden';
            }
        }
    },


    /**
     * @param  object  record
     * @return Boolean
     */
    hasOrderPaymentName: function (record) {
        return record.getPaymentStore &&
            record.getPaymentStore.data &&
            record.getPaymentStore.data.items &&
            record.getPaymentStore.data.items[0] &&
            record.getPaymentStore.data.items[0].data &&
            record.getPaymentStore.data.items[0].data.name;
    },

    /**
     * @param  object  record
     * @return string
     */
    getOrderPaymentName: function (record) {
        var me = this;

        if (me.hasOrderPaymentName(record)) {
            return record.getPaymentStore.data.items[0].data.name;
        }

        return '';
    },


});
//{/block}