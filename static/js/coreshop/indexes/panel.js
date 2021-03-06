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

pimcore.registerNS('pimcore.plugin.coreshop.indexes.panel');

pimcore.plugin.coreshop.indexes.panel = Class.create(pimcore.plugin.coreshop.abstract.panel, {

    layoutId: 'coreshop_indexes_panel',
    storeId : 'coreshop_indexes',
    iconCls : 'coreshop_icon_indexes',
    type : 'indexes',

    url : {
        add : '/plugin/CoreShop/admin_indexes/add',
        delete : '/plugin/CoreShop/admin_indexes/delete',
        get : '/plugin/CoreShop/admin_indexes/get',
        list : '/plugin/CoreShop/admin_indexes/list'
    },

    typesStore : null,

    /**
     * constructor
     */
    initialize: function () {
        var proxy = new Ext.data.HttpProxy({
            url : '/plugin/CoreShop/admin_indexes/get-types'
        });

        var reader = new Ext.data.JsonReader({}, [
            { name:'name' }
        ]);

        this.typesStore = new Ext.data.Store({
            restful:    false,
            proxy:      proxy,
            reader:     reader,
            autoload:   true
        });
        this.typesStore.load();

        this.getGettersStore();
        this.getInterpretersStore();

        // create layout
        this.getLayout();

        this.panels = [];
    },

    getGettersStore : function () {
        var store = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url : '/plugin/CoreShop/admin_indexes/get-available-getters',
                reader: {
                    type: 'json',
                    rootProperty : 'data'
                }
            }
        });

        store.load(function () {
            store.insert(0, { type : null, name : t('none') });
        });

        pimcore.globalmanager.add('coreshop_index_getters', store);
    },

    getInterpretersStore : function () {
        var store = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url : '/plugin/CoreShop/admin_indexes/get-available-interpreters',
                reader: {
                    type: 'json',
                    rootProperty : 'data'
                }
            }
        });

        store.load(function () {
            store.insert(0, { type : null, name : t('none') });
        });

        pimcore.globalmanager.add('coreshop_index_interpreters', store);
    }
});
