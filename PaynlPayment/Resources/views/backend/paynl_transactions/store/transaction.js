Ext.define('Shopware.apps.PaynlTransactions.store.Transaction', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'PaynlTransactions'
        };
    },
    model: 'Shopware.apps.PaynlTransactions.model.Transaction'
});