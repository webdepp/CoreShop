/**
 * CoreShop
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS('pimcore.document.tags.product');
pimcore.document.tags.product = Class.create(pimcore.document.tags.renderlet, {

    defaultHeight: 300,

    initialize: function (id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.options = this.parseOptions(options);

        this.data = {};

        if (!data) {
            data = {};
        }

        // height management
        this.defaultHeight = 100;
        if (this.options.defaultHeight) {
            this.defaultHeight = this.options.defaultHeight;
        }

        if (!this.options.height && !data.path) {
            this.options.height = this.defaultHeight;
        }

        this.setupWrapper();

        this.options.name = id + '_editable';
        this.options.border = false;
        this.options.bodyStyle = 'min-height: 40px;';

        this.element = new Ext.Panel(this.options);

        this.element.on('render', function (el) {

            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            this.getBody().setStyle({
                overflow: 'auto'
            });

            this.getBody().insertHtml('beforeEnd', '<div class="pimcore_tag_droptarget"></div>');
            this.getBody().addCls('pimcore_tag_snippet_empty');

            el.getEl().on('contextmenu', this.onContextMenu.bind(this));

        }.bind(this));

        this.element.render(id);

        // insert snippet content
        if (data) {
            this.data = data;
            if (this.data.id) {
                this.updateContent();
            }
        }
    },

    onNodeDrop: function (target, dd, e, data)
    {
        var record = data.records[0];

        // get path from nodes data
        this.data.id = record.get('id');
        this.data.type = record.get('elementType');
        this.data.subtype = record.get('type');

        if (this.options.reload) {
            this.reloadDocument();
        } else {
            this.updateContent();
        }

        return true;
    },

    dndAllowed: function (data) {
        return data.records[0].get('className') === coreshop.settings.classMapping.product;
    },

    onNodeOver: function (target, dd, e, data)
    {
        if (this.dndAllowed(data)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        } else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)
        var bodyId = this.element.getEl().query('.x-panel-body')[0].getAttribute('id');
        return Ext.get(bodyId);
    },

    updateContent: function (path) {

        this.getBody().removeCls('pimcore_tag_snippet_empty');
        this.getBody().dom.innerHTML = '<br />&nbsp;&nbsp;Loading ...';

        var params = this.data;
        Ext.apply(params, this.options);

        try {
            // add the id of the current document, so that the renderlet knows in which document it is embedded
            // this information is then grabbed in Pimcore_Controller_Action_Frontend::init() to set the correct locale
            params['pimcore_parentDocument'] = window.editWindow.document.id;
        } catch (e) {

        }

        Ext.Ajax.request({
            method: 'get',
            url: '/plugin/CoreShop/product/preview',
            success: function (response) {
                this.getBody().dom.innerHTML = response.responseText;
                this.getBody().insertHtml('beforeEnd', '<div class="pimcore_tag_droptarget"></div>');
                this.updateDimensions();
            }.bind(this),
            params: params
        });
    },

    updateDimensions: function () {
        this.getBody().setStyle({
            height: 'auto'
        });
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();

        if (this.data['id']) {
            menu.add(new Ext.menu.Item({
                text: t('empty'),
                iconCls: 'pimcore_icon_delete',
                handler: function () {
                    var height = this.options.height;
                    if (!height) {
                        height = this.defaultHeight;
                    }

                    this.data = {};
                    this.getBody().update('');
                    this.getBody().insertHtml('beforeEnd', '<div class="pimcore_tag_droptarget"></div>');
                    this.getBody().addCls('pimcore_tag_snippet_empty');
                    this.getBody().setHeight(height + 'px');

                    if (this.options.reload) {
                        this.reloadDocument();
                    }

                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: 'pimcore_icon_open',
                handler: function () {
                    if (this.data.id) {
                        pimcore.helpers.openElement(this.data.id, this.data.type, this.data.subtype);
                    }
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: 'pimcore_icon_fileexplorer',
                handler: function (item) {
                    item.parentMenu.destroy();
                    pimcore.helpers.selectElementInTree(this.data.type, this.data.id);
                }.bind(this)
            }));
        }

        menu.showAt(e.getXY());

        e.stopEvent();
    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {});
    },

    addDataFromSelector: function (item) {
        if (item) {
            // get path from nodes data
            this.data.id = item.id;
            this.data.type = item.type;
            this.data.subtype = item.subtype;

            if (this.options.reload) {
                this.reloadDocument();
            } else {
                this.updateContent();
            }
        }
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return 'product';
    }
});
