Ext.define('Shopware.apps.PaynlTransactions', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.PaynlTransactions',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'list.Window',
        'list.Transaction'
    ],

    models: [ 'Transaction' ],
    stores: [ 'Transaction' ],

    launch: function() {
        return this.getController('Main').mainWindow;
    }
});