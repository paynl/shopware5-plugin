Ext.define('Shopware.apps.PaynlTransactions.model.Transaction', {
    extend: 'Shopware.data.Model',

    configure: function () {
        return {
            controller: 'PaynlTransactions'
        };
    },

    fields: [
        { name : 'paynlPaymentId', type: 'int'},
        { name : 'transactionId', type: 'string'},
        { name : 'amount', type: 'float'},
        { name : 'status', type: 'json'},
        { name : 'order', type: 'json'},
        { name : 'exceptions', type: 'json'},
        { name : 'customer', type: 'json'},
        { name : 'createdAt', type: 'datetime'},
        { name : 'updatedAt', type: 'datetime'},
    ],
});