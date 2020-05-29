Ext.define('Plugin.turbo.RSSListPanel', {

    extend: 'Ext.grid.GridPanel',

    columns: [
        {header: "Название", width: 350, dataIndex: 'name'},
        {flex: 1, header: "Alias", width: 300, dataIndex: 'fileName'},
        {header: "Ссылка", width: 500, dataIndex: 'pathToDinamic'}
    ],

    selModel: {
        mode: 'SINGLE',
        listeners: {
            'selectionchange': {
                fn: function (sm) {
                    var hs = sm.hasSelection();
                    Ext.getCmp('tb_rss_edit').setDisabled(!hs);
                    Ext.getCmp('tb_rss_delete').setDisabled(!hs);
                },
                scope: this
            }
        }
    },

    initComponent: function () {

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            remoteSort: true,
            fields: ['name', 'fileName', 'pathToDinamic'],
            sortInfo: {field: "name", direction: "ASC"},
            totalProperty: 'total',
            proxy: {
                type: 'ajax',
                url: '/plugins/turbo/data/data_rss_lists.php',
                simpleSortMode: true,
                reader: {
                    root: 'rows',
                    idProperty: 'id'
                }
            }
        });

        this.tbar = new Ext.Toolbar({
            items: [
                {
                    tooltip: Config.Lang.reload,
                    iconCls: 'icon-reload',
                    handler: function (btn) {
                        btn.up('grid').getStore().load();
                    }
                }, '-',
                {
                    id: 'tb_rss_new',
                    iconCls: 'icon-new',
                    text: Config.Lang.add,
                    tooltip: '<b>Добавить Turbo</b>',
                    handler: function () {
                        this.edit(0);
                    },
                    scope: this
                }, '-',
                {
                    id: 'tb_rss_edit',
                    disabled: true,
                    iconCls: 'icon-edit',
                    text: Config.Lang.edit,
                    tooltip: '<b>Изменить Turbo</b>',
                    handler: function () {
                        this.edit(this.getSelectionModel().getSelection()[0].getId());
                    },
                    scope: this
                },
                {
                    id: 'tb_rss_delete',
                    disabled: true,
                    iconCls: 'icon-delete',
                    text: Config.Lang.remove,
                    tooltip: '<b>Удалить Turbo</b>',
                    handler: function () {
                        this.delete_list();
                    },
                    scope: this
                }
            ]
        });

        this.on({
            'beforedestroy': function () {
                if (this.propertiesWin) this.propertiesWin.close();
                this.propertiesWin = false;
                if (this.chooseWin) this.chooseWin.close();
                this.chooseWin = false;
            },
            'celldblclick': function () {
                this.edit(this.getSelectionModel().getSelection()[0].getId());
            },
            scope: this
        });

        this.fireEvent('activate');
        this.callParent();
        this.reload();
    },

    border: false,
    loadMask: true,
    stripeRows: true,


    edit: function (id) {
        if (!this.propertiesWin) {
            this.propertiesWin = Ext.create('Plugin.turbo.RSSListPropertiesWindow');
            this.propertiesWin.on('listChanged', function (id, item) {
                this.reload();
            }, this);
        }
        this.propertiesWin.show(id);
    },


    delete_list: function () {
        Ext.MessageBox.confirm('Удалить Turbo', 'Вы уверены?', function (btn) {
            if (btn == 'yes') this.call('delete_list');
        }, this);
    },

    call: function (action) {
        Ext.Ajax.request({
            url: '/plugins/turbo/action_rss_lists.php',
            params: {
                action: action,
                id: this.getSelectionModel().getSelection()[0].getId()
            },
            scope: this,
            success: function (resp) {
                this.store.reload();
            }
        });
    },

    reload: function () {
        this.store.load();
    }
});