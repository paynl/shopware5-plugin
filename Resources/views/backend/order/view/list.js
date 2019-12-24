//{block name="backend/order/view/list/list" append}
Ext.override(Shopware.apps.Order.view.list.List, {
    paymentStatus: {
        COMPLETELY_PAID: 12,
        RESERVED: 18,
        THE_CREDIT_HAS_BEEN_ACCEPTED: 32,
        RE_CREDITING: 20,
        CANCELLED: 35,
        PARTIALLY_PAID: 11
    },

    getColumns: function () {
        var me = this;
        var columns = me.callParent(arguments);

        columns.push(me.createPaynlColumn());

        return columns;
    },

    createPaynlColumn: function () {
        var me = this;

        var columnAction = Ext.create('Ext.grid.column.Action', {
            width: 50,
            items: [
                me.getPaynlInfoButton(),
                me.getPaynlRefundButton(),
            ],
            header: me.snippets.columns.paynl || 'Paynl'
        });

        return columnAction;
    },

    getPaynlInfoButton: function () {
        var me = this;

        return {
            action: 'notice',
            tooltip: '{s name="tooltip/paynl"}Open in Pay.nl{/s}',
            iconCls: 'sprite--paynl-logo',
            handler: function (view, rowIndex, colIndex, item, opts, record) {


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
            getClass: function (value, metadata, record) {
                if (
                    me.hasOrderPaymentName(record) &&
                    me.getOrderPaymentName(record).substring(0, 6) === 'paynl_'
                ) {
                    return '';
                }
                return 'paynl-hidden';
            }
        }
    },

    getPaynlRefundButton: function () {
        var me = this;

        return {
            action: 'notice',
            tooltip: '{s name="tooltip/paynl_refund"}Refund this transaction{/s}',
            iconCls: 'sprite-arrow-circle-315',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                return Shopware.ModuleManager.createSimplifiedModule("PaynlRefundForm?paynlPaymentId=" + record.data.transactionId, {
                    title: "Refund Order " + record.data.transactionId,
                    width: '800px',
                    height: '600px'
                });
            },
            getClass: function (value, metadata, record) {
                if (
                    me.hasOrderPaymentName(record) &&
                    me.getOrderPaymentName(record).substring(0, 6) === 'paynl_' &&
                    (
                        record.data.cleared == me.paymentStatus.COMPLETELY_PAID ||
                        record.data.cleared == me.paymentStatus.RE_CREDITING
                    )
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