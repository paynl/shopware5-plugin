Ext.define('Shopware.apps.PaynlTransactions.view.list.Transaction', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.transaction-listing-grid',
    region: 'center',

    configure: function () {
        var me = this;

        return {
            addButton: false,
            editColumn: false,
            deleteColumn: false,
            deleteButton: false,
            rowEditing: false,

            columns: {
                id: {
                    header: 'Id'
                },
                createdAt: {
                    renderer: me.formatDateTime
                },
                updatedAt: {
                    renderer: me.formatDateTime
                },
                paynlPaymentId: {
                    header: 'Paynl Id'
                },
                transactionId: {
                    header: 'TransactionId'
                },
                order: {
                    header: 'Order number',
                    renderer: function (order) {
                        return order ? order.number : '';
                    }
                },
                amount: {},
                customer: {
                    header: 'Customer name',
                    renderer: function (customer) {
                        return customer ? customer.firstname + ' ' + customer.lastname : '';
                    }
                },
                status: {
                    header: 'Status',
                    renderer: function (status) {
                        return status ? status.name : '';
                    }
                }
            }
        }
    },
    formatDateTime(date){
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var seconds = date.getSeconds();
        minutes = minutes < 10 ? '0'+minutes : minutes;
        seconds = seconds < 10 ? '0'+seconds : seconds;
        var strTime = hours + ':' + minutes + ':'+seconds;
        return date.getDate() + "-" + (date.getMonth()+1) + "-" + date.getFullYear() + " " + strTime;
    },
    createActionColumnItems: function () {
        var me = this,
            items = me.callParent(arguments);
        items.push({
            action: 'notice',
            tooltip: '{s name="tooltip/paynl"}Open in Pay.nl{/s}',
            iconCls: 'sprite--paynl-logo',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                var url = "https://admin.pay.nl/transactions/details/" + record.get('transactionId');
                window.open(url, 'paynl_info', 'toolbar=no,' +
                    ' location=no,' +
                    ' status=no,' +
                    ' menubar=no,' +
                    ' scrollbars=yes,' +
                    ' resizable=yes,' +
                    ' width=800,' +
                    ' height=800');
            }
        });
        // Customer details button
        items.push({
            iconCls: 'sprite-user',
            tooltip: '{s name="tooltip/customer"}Open customer details{/s}',

            handler: function (view, rowIndex, colIndex, item, opts, record) {

                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Customer',
                    action: 'detail',
                    params: {
                        customerId: record.get('customer').id
                    }
                });
            }
        });

        // Order details button
        items.push({
            iconCls: 'sprite-shopping-basket',
            tooltip: '{s name="tooltip/order"}Open order details{/s}',

            handler: function (view, rowIndex, colIndex, item, opts, record) {
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.get('order').id
                    }
                });
            }
        });
        return items;
    }
});