<?php
/**
 * CoreShop.
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

namespace CoreShop\Model;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Object\AbstractObject;

/**
 * Class AdminStyle
 * @package CoreShop\Model\Product
 */
class AdminStyle extends \Pimcore\Model\Element\AdminStyle
{
    /**
     * @var AbstractObject
     */
    protected $element;

    /**
     * AdminStyle constructor.
     * @param $element
     */
    public function __construct($element)
    {
        parent::__construct($element);

        if ($element instanceof Product) {
            $this->elementIconClass = 'coreshop_icon_product_green';
        }
        else if ($element instanceof Order) {
            $this->elementIconClass = 'coreshop_icon_order';
        }
        else if ($element instanceof Order\Item) {
            $this->elementIconClass = 'coreshop_icon_product';
        }
        else if ($element instanceof User) {
            $this->elementIconClass = 'coreshop_icon_customers';
        }
        else if ($element instanceof User\Address) {
            $this->elementIconClass = 'coreshop_icon_address';
        }
        else if ($element instanceof Category) {
            $this->elementIconClass = 'coreshop_icon_category';
        }
        else if ($element instanceof Order\Invoice) {
            $this->elementIconClass = 'coreshop_icon_orders_invoice';
        }
        else if($element instanceof Customer\Group) {
            $this->elementIconClass = 'coreshop_icon_customer_group';
        }
        else if($element instanceof Manufacturer) {
            $this->elementIconClass = 'coreshop_icon_manufacturer';
        }
        else if($element instanceof Cart) {
            $this->elementIconClass = "coreshop_icon_cart";
        }
        else if($element instanceof Order\Payment) {
            $this->elementIconClass = "coreshop_icon_payment";
        }

        $this->element = $element;
    }

    /**
     * @return array
     */
    public function getElementQtipConfig()
    {
        if($this->element instanceof Product) {
            $image = $this->element->getImage();

            $text = sprintf("<h1>%s</h1>", $this->element->getArticleNumber());

            if($image instanceof Image) {
                $thumbnail = $image->getThumbnail("coreshop_productDetailThumbnail");

                $text .= sprintf("<p>%s</p>", $thumbnail->getHTML());
            }
            return [
                "title" => $this->element->getName() . " (" . $this->element->getId() . ")",
                "text" => $text
            ];
        }
        else if($this->element instanceof Order) {
            $translate = new \Pimcore\Translate\Admin(\Zend_Registry::get("Zend_Locale"));

            $text = sprintf(
                '<p style="text-align: right">
                        %s %s<br/>
                        %s %s<br/>
                        %s %s<br/>
                        <br/>
                        %s (%d)
                </p>',
                $translate->translate("Subtotal"),
                \CoreShop::getTools()->formatPrice($this->element->getSubtotal(), null, $this->element->getCurrency()),
                $translate->translate("Tax"),
                \CoreShop::getTools()->formatPrice($this->element->getTotalTax(), null, $this->element->getCurrency()),
                $translate->translate("Total"),
                \CoreShop::getTools()->formatPrice($this->element->getTotal(), null, $this->element->getCurrency()),
                $translate->translate("Invoices"),
                count($this->element->getInvoices())
            );

            return [
                "title" => ($this->element->getOrderState() instanceof Order\State ? $this->element->getOrderState()->getName() : $translate->translate("Unknown")) . " (" . $this->element->getId() . ")",
                "text" => $text
            ];
        }

        return parent::getElementQtipConfig();
    }
}
