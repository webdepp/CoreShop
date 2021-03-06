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

pimcore.registerNS('pimcore.plugin.coreshop.pricerules.panel');

pimcore.plugin.coreshop.pricerules.panel = Class.create(pimcore.plugin.coreshop.rules.panel, {

    /**
     * @var string
     */
    layoutId: 'coreshop_price_rules_panel',
    storeId : 'coreshop_pricerules',
    iconCls : 'coreshop_icon_price_rule',
    type : 'cart_pricerules',

    url : {
        add : '/plugin/CoreShop/admin_price-rule/add',
        delete : '/plugin/CoreShop/admin_price-rule/delete',
        get : '/plugin/CoreShop/admin_price-rule/get',
        list : '/plugin/CoreShop/admin_price-rule/list',
        config : '/plugin/CoreShop/admin_price-rule/get-config'
    },

    getItemClass : function () {
        return pimcore.plugin.coreshop.pricerules.item;
    }
});
