<!-- CORESHOP DEBUG -->
<script type="text/javascript" src="/plugins/CoreShop/static/js/frontend/debug.js"></script>
<link rel="stylesheet" type="text/css" href="/plugins/CoreShop/static/css/debug.css" />

<div id="coreshop-debug">

    <div class="coreshop-debug-panel">
        <div class="coreshop-debug-panel-heading">
            <h3 class="coreshop-debug-panel-title">CoreShop <?=$this->translate("coreshop_debug")?></h3>
            <span class="coreshop-debug-clickable coreshop-debug-panel-collapsed"><i class="glyphicon glyphicon-chevron-up"></i></span>
        </div>
        <div class="coreshop-debug-panel-body" style="display:none">
            <table class="coreshop-debug-table">
                <?php if(\Pimcore\Model\Staticroute::getCurrentRoute() instanceof \Pimcore\Model\Staticroute) { ?>
                    <tr>
                        <td>Staticroute</td>
                        <td><?=\Pimcore\Model\Staticroute::getCurrentRoute()->getName()?></td>
                    </tr>
                <?php } ?>
                <?php if(\CoreShop\Model\Configuration::multiShopEnabled()) { ?>
                    <tr>
                        <td><?=$this->translate("coreshop_shop")?></td>
                        <td><?=CoreShop\Model\Shop::getShop()->getName() ?> (<?=\CoreShop\Model\Shop::getShop()->getId()?>)</td>
                    </tr>
                <?php } ?>
                <tr>
                    <td><?=$this->translate("coreshop_country")?></td>
                    <td><?=\CoreShop\Tool::getCountry()->getName() ?> (<?=\CoreShop\Tool::getCountry()->getId()?>)</td>
                </tr>
                <tr>
                    <td><?=$this->translate("coreshop_currency")?></td>
                    <td><?=\CoreShop\Tool::getCurrency()->getName() ?> (<?=\CoreShop\Tool::getCurrency()->getId()?>)</td>
                </tr>

                <?php if(\CoreShop\Tool::getUser() instanceof \CoreShop\Model\User) { ?>
                    <tr>
                        <td><?=$this->translate("coreshop_user")?></td>
                        <td><?=\CoreShop\Tool::getUser()->getEmail() ?> (<?=\CoreShop\Tool::getUser()->getId()?>)</td>
                    </tr>

                    <?php foreach(\CoreShop\Tool::getUser()->getCustomerGroups() as $group) { ?>
                        <tr>
                            <td><?=$this->translate("coreshop_customer_group")?></td>
                            <td><?=$group->getName() ?> (<?=$group->getId()?>)</td>
                        </tr>
                    <?php } ?>
                <?php } ?>

                <?php if($this->product instanceof \CoreShop\Model\Product) {
                    ?>
                    <tr>
                        <td><?=$this->translate("coreshop_product")?></td>
                        <td><?=$this->product->getName()?> (<?=$this->product->getId()?>)</td>
                    </tr>
                    <tr>
                        <td><?=$this->translate("coreshop_retail_price")?></td>
                        <td><?=\CoreShop\Tool::formatPrice($this->product->getRetailPrice())?></td>
                    </tr>
                    <tr>
                        <td><?=$this->translate("coreshop_price")?></td>
                        <td><?=\CoreShop\Tool::formatPrice($this->product->getPrice(true))?></td>
                    </tr>
                    <tr>
                        <td><?=$this->translate("coreshop_price_rules")?></td>
                        <td>
                            <table class="coreshop-debug-table">
                                <thead>
                                <tr>
                                    <td><?=$this->translate("corshop_price_rule_name")?></td>
                                    <td><?=$this->translate("coreshop_price")?></td>
                                    <td><?=$this->translate("coreshop_discount")?></td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $priceRules = $this->product->getValidSpecificPriceRules();
                                $specificPrice = $this->product->getSpecificPrice();

                                foreach($priceRules as $rule) {
                                    ?>
                                    <tr>
                                        <td><?=$rule->getName()?></td>
                                        <td><?=\CoreShop\Tool::formatPrice($rule->getPrice($this->product))?></td>
                                        <td><?=\CoreShop\Tool::formatPrice($rule->getDiscount($specificPrice, $this->product))?></td>
                                    </tr>
                                    <?php
                                }?>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <?php if($this->product->getTaxRule() instanceof \CoreShop\Model\TaxRuleGroup) { ?>
                        <tr>
                            <td><?=$this->translate("coreshop_taxrulegroups")?></td>
                            <td><?=$this->product->getTaxRule()->getName()?> (<?=$this->product->getTaxRule()->getId()?>)</td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><?=$this->translate("coreshop_tax_rate")?></td>
                        <td><?=\CoreShop\Tool::formatTax($this->product->getTaxRate()/100)?></td>
                    </tr>

                <?php } ?>

                <?php if(\CoreShop\Tool::prepareCart()->getId()) { ?>
                    <tr>
                        <td><?=$this->translate("coreshop_cart")?></td>
                        <td><?=\CoreShop\Tool::prepareCart()->getId()?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>