Ext.define('Plugin.turbo.RSSListPropertiesWindow', {

    extend: 'Ext.Window',

    closeAction: 'hide',
    title: '',
    width: 510,
    height: 300,
    layout: 'vbox',
    modal: true,
    resizable: false,
    border: false,

    listId: 0,

    initComponent: function () {

        this.dirSetListStore = new Ext.data.JsonStore({
            autoDestroy: true,
            fields: ['id', 'name'],
            proxy: {
                type: 'ajax',
                extraParams: {'id': 0},
                url: '/plugins/turbo/data/data_catalogs_list.php',
                simpleSortMode: true,
                reader: {
                    root: 'rows',
                    idProperty: 'id'
                }
            }
        });

        this.dirSetList = Ext.create('Cetera.field.DirSet', {
            name: 'dirs',
            from: '0',
            height: 98,
            fieldLabel: 'Разделы',
            store: this.dirSetListStore
        });

        this.tabs = new Ext.TabPanel({
            deferredRender: false,
            activeTab: 0,
            plain: true,
            border: false,
            bodyStyle: 'background: none',
            height: 250,
            defaults: {bodyStyle: 'background:none; padding:5px'},
            items: [{
                title: 'Основные',
                layout: 'form',
                defaults: {anchor: '0'},
                defaultType: 'textfield',
                items: [
                    {
                        fieldLabel: 'Название',
                        name: 'name',
                        allowBlank: false
                    }, {
                        fieldLabel: 'Alias',
                        name: 'fileName',
                        allowBlank: false,
                        regex: /[a-zA-Z0-9-_]/,
                        regexText: 'Alias может содержать только латинские буквы, цифры и символы -_'
                    },
                    this.dirSetList,
                    {
                        xtype: 'combobox',
                        fieldLabel: 'Тип Turbo',
                        id: 'rssTypeBox',
                        store: new Ext.data.ArrayStore({
                            autoDestroy: true,
                            fields: ['id', 'name'],
                            data: [["4", "Turbo"], ["5", "AMP"], ["6", "Telegram"]]
                        }),
                        valueField: 'id',
                        displayField: 'name',
                        queryMode: 'local',
                        allowBlank: false,
                        name: 'type'
                    }
                ]
            }]
        });

        this.form = new Ext.FormPanel({
            labelWidth: 140,
            border: false,
            width: 500,
            bodyStyle: 'background: none',
            method: 'POST',
            waitMsgTarget: true,
            url: '/plugins/turbo/action_rss_lists.php',
            items: this.tabs
        });

        this.items = this.form;

        this.buttons = [{
            text: 'OK',
            scope: this,
            handler: this.submit
        }, {
            text: 'Отмена',
            scope: this,
            handler: function () {
                this.hide();
            }
        }];

        this.callParent();
    },

    show: function (id) {
        this.form.getForm().reset();
        this.tabs.setActiveTab(0);

        this.callParent();

        this.listId = id;
        if (id > 0) {
            Ext.Ajax.request({
                url: '/plugins/turbo/action_rss_lists.php',
                params: {
                    action: 'get_list',
                    id: this.listId
                },
                scope: this,
                success: function (resp) {
                    var obj = Ext.decode(resp.responseText);
                    this.setTitle('Свойства: ' + obj.rows.name);
                    this.form.getForm().setValues(obj.rows);
                }
            });
        } else {
            this.setTitle('Новый Turbo');
        }
        this.dirSetListStore.proxy.extraParams['id'] = this.listId;
        this.dirSetListStore.reload();
    },

    submit: function () {

        var params = {
            action: 'save_list',
            id: this.listId,
            dirs_parse: "[" + this.dirSetList.store.data.keys.join(",") + "]"
        };

        this.form.getForm().submit({
            params: params,
            scope: this,
            waitMsg: 'Сохранение...',
            success: function (resp) {
                this.fireEvent('listChanged', this.listId, this.form.getForm());
                this.hide();
            },
            failure: function (resp, mess) {
                Ext.MessageBox.alert("Ошибка", mess.result.errors.join("<br>"));
            }
        });
    }
});