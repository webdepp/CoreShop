<?php
/**
 * CoreShop
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015 Dominik Pfaffenbauer (http://dominik.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Model;

use CoreShop\Exception\UnsupportedException;
use CoreShop\Model\Plugin\Payment as CorePayment;
use CoreShop\Plugin;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Object;
use Pimcore\Model\Object\CoreShopPayment;
use Pimcore\Model\Version;

class Order extends Base
{
    /**
     * Creates next OrderNumber
     *
     * @return int|string
     */
    public static function getNextOrderNumber()
    {
        $number = NumberRange::getNextNumberForType("order");

        return self::getValidOrderNumber($number);
    }

    /**
     * Converts any Number to a valid OrderNumber with Suffix and Prefix
     *
     * @param $number
     * @return string
     */
    public static function getValidOrderNumber($number)
    {
        $prefix = Configuration::get("SYSTEM.INVOICE.PREFIX");
        $suffix = Configuration::get("SYSTEM.INVOICE.SUFFIX");

        if ($prefix) {
            $number = $prefix . $number;
        }

        if ($suffix) {
            $number = $number . $suffix;
        }

        return $number;
    }

    /**
     * Import a Cart to the Order
     *
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function importCart(Cart $cart)
    {
        $items = array();
        $i = 1;
        
        foreach ($cart->getItems() as $cartItem) {
            $item = new Object\CoreShopOrderItem();
            $item->setKey($i);
            $item->setParent(Object\Service::createFolderByPath($this->getFullPath() . "/items/"));
            $item->setPublished(true);
            
            $item->setProduct($cartItem->getProduct());
            $item->setWholesalePrice($cartItem->getProduct()->getWholesalePrice());
            $item->setRetailPrice($cartItem->getProduct()->getRetailPrice());
            $item->setTax($cartItem->getProduct()->getTaxAmount());
            $item->setPrice($cartItem->getProduct()->getPrice());
            $item->setAmount($cartItem->getAmount());
            $item->setExtraInformation($cartItem->getExtraInformation());
            $item->setIsGiftItem($cartItem->getIsGiftItem());
            $item->save();

            //Stock Management
            $cartItem->getProduct()->updateQuantity(-$cartItem->getAmount());
            
            $items[] = $item;
            
            $i++;
        }

        $this->setDiscount($cart->getDiscount());
        $this->setPriceRule($cart->getPriceRule());
        $this->setItems($items);
        $this->save();

        //Store Order into cart for statistic purpose
        $cart->setOrder($this);
        $cart->save();
        
        return true;
    }

    /**
     * Create a new Payment
     *
     * @param CorePayment $provider
     * @param $amount
     * @return Object\CoreShopPayment
     * @throws \Exception
     */
    public function createPayment(CorePayment $provider, $amount, $paid = false)
    {
        $payment = new Object\CoreShopPayment();
        $payment->setKey(uniqid());
        $payment->setPublished(true);
        $payment->setParent(Object\Service::createFolderByPath($this->getFullPath() . "/payments/"));
        $payment->setAmount($amount);
        $payment->setTransactionIdentifier(uniqid());
        $payment->setProvider($provider->getIdentifier());
        $payment->setPayed($paid);
        $payment->save();
        
        $this->addPayment($payment);
        
        return $payment;
    }

    /**
     * Add a new Payment
     *
     * @param CoreShopPayment $payment
     */
    public function addPayment(CoreShopPayment $payment)
    {
        $payments = $this->getPayments();
        
        if (!is_array($payments)) {
            $payments = array();
        }
            
        $payments[] = $payment;
        
        $this->setPayments($payments);
        $this->save();
    }

    /**
     * Calculates the subtotal of the Order
     *
     * @return int
     */
    public function getSubtotal()
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }

    /**
     * Calculates the total of the Order
     *
     * @return int
     */
    public function getTotal()
    {
        $subtotal = $this->getSubtotal();
        $shipping = $this->getShipping();
        $discount = $this->getDiscount();
        $paymentFee = $this->getPaymentFee();

        return ($subtotal  + $shipping + $paymentFee) - $discount;
    }

    /**
     * Returns the total payed amount for the Order
     *
     * @return float|int
     * @throws UnsupportedException
     */
    public function getPayedTotal()
    {
        $totalPayed = 0;

        foreach ($this->getPayments() as $payment) {
            if ($payment->getPayed()) {
                $totalPayed += $payment->getAmount();
            }
        }

        return $totalPayed;
    }

    /**
     * Returns Customers shipping address
     *
     * @return Object\Fieldcollection\Data\CoreShopUserAddress|bool
     */
    public function getCustomerShippingAddress()
    {
        $address = $this->getShippingAddress()->getItems();

        if (count($address) > 0) {
            return $address[0];
        }

        return false;
    }

    /**
     * Returns Customers billing address
     *
     * @return Object\Fieldcollection\Data\CoreShopUserAddress|bool
     */
    public function getCustomerBillingAddress()
    {
        $address = $this->getBillingAddress()->getItems();

        if (count($address) > 0) {
            return $address[0];
        }

        return false;
    }

    /**
     * checks if shipping and billing addresses are the same
     *
     * @returns boolean
     */
    public function isShippingAndBillingAddressEqual()
    {
        $shipping = $this->getCustomerShippingAddress();
        $billing = $this->getCustomerBillingAddress();

        $billingVars = $billing->getObjectVars();
        $shippingVars = $shipping->getObjectVars();

        foreach ($shippingVars as $key => $value) {
            if ($key === "fieldname") {
                continue;
            }

            if (array_key_exists($key, $billingVars)) {
                if (!is_object($value)) {
                    if ($billingVars[$key] !== $value) {
                        return false;
                    }
                } else {
                    if ($value instanceof Object\AbstractObject) {
                        if ($value->getId() !== $billingVars[$key]->getId()) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @return bool|\CoreShop\Model\Plugin\Payment
     * @throws UnsupportedException
     */
    public function getPaymentProviderObject()
    {
        $paymentProvider = $this->getPaymentProvider();

        return Plugin::getPaymentProvider($paymentProvider);
    }

    /**
     * Pimcore: When save is called from Pimcore, check for changes of the OrderState
     *
     * @return int
     */
    public function save()
    {
        Version::disable();

        if (isset($_REQUEST['data'])) {
            try {
                $data = \Zend_Json::decode($_REQUEST['data']);

                if (isset($data['orderState'])) {
                    $orderStep = OrderState::getById($data['orderState']);

                    unset($_REQUEST['data']);

                    if ($orderStep instanceof OrderState) {
                        $orderStep->processStep($this);
                    }
                }
            } catch (\Exception $ex) {
                \Logger::error($ex);
            }
        }

        Version::enable();

        parent::save();
    }

    /**
     * Get Invoice for Order
     *
     * @return bool|mixed|Document
     */
    public function getInvoice()
    {
        //Check if invoice has already been generated
        $document = $this->getProperty("invioce");

        if ($document instanceof Document) {
            return $document;
        }

        return Invoice::generateInvoice($this);
    }

    /**
     * set discount for order
     * this method has to be overwritten in Pimcore Object
     *
     * @param OrderState $state
     * @throws UnsupportedException
     */
    public function setOrderState($state)
    {
        throw new UnsupportedException("setOrderState is not supported for " . get_class($this));
    }

    /**
     * set discount for order
     * this method has to be overwritten in Pimcore Object
     *
     * @param float $discount
     * @throws UnsupportedException
     */
    public function setDiscount($discount)
    {
        throw new UnsupportedException("setDiscount is not supported for " . get_class($this));
    }

    /**
     * returns discount for order
     * this method has to be overwritten in Pimcore Object
     *
     * @throws UnsupportedException
     * @return float
     */
    public function getDiscount()
    {
        throw new UnsupportedException("getDiscount is not supported for " . get_class($this));
    }

    /**
     * returns customer for order
     * this method has to be overwritten in Pimcore Object
     *
     * @throws UnsupportedException
     * @return User
     */
    public function getCustomer()
    {
        throw new UnsupportedException("getCustomer is not supported for " . get_class($this));
    }

    /**
     * returns shipping for order
     * this method has to be overwritten in Pimcore Object
     *
     * @throws UnsupportedException
     * @return float
     */
    public function getShipping()
    {
        throw new UnsupportedException("getShipping is not supported for " . get_class($this));
    }

    /**
     * returns paymentFee for order
     * this method has to be overwritten in Pimcore Object
     *
     * @throws UnsupportedException
     * @return float
     */
    public function getPaymentFee()
    {
        throw new UnsupportedException("getPaymentFee is not supported for " . get_class($this));
    }

    /**
     * set PriceRule for order
     * this method has to be overwritten in Pimcore Object
     *
     * @param PriceRule $priceRule
     * @throws UnsupportedException
     */
    public function setPriceRule($priceRule)
    {
        throw new UnsupportedException("setPriceRule is not supported for " . get_class($this));
    }

    /**
     * set items for order
     * this method has to be overwritten in Pimcore Object
     *
     * @param OrderItem[] $items
     * @throws UnsupportedException
     */
    public function setItems($items)
    {
        throw new UnsupportedException("setItems is not supported for " . get_class($this));
    }

    /**
     * returns payments
     * this method has to be overwritten in Pimcore Object
     *
     * @throws UnsupportedException
     * @return CoreShopPayment[]
     */
    public function getPayments()
    {
        throw new UnsupportedException("getPayments is not supported for " . get_class($this));
    }

    /**
     * sets payments
     * this method has to be overwritten in Pimcore Object
     *
     * @param CoreShopPayment[] $payments
     * @throws UnsupportedException
     */
    public function setPayments($payments)
    {
        throw new UnsupportedException("setPayments is not supported for " . get_class($this));
    }

    /**
     * returns orderitems
     * this method has to be overwritten in Pimcore Object
     *
     * @throws UnsupportedException
     * @return OrderItem[]
     */
    public function getItems()
    {
        throw new UnsupportedException("getItems is not supported for " . get_class($this));
    }

    /**
     * shipping address
     *
     * @throws UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getShippingAddress()
    {
        throw new UnsupportedException("getShippingAddress is not supported for " . get_class($this));
    }

    /**
     * billing address
     *
     * @throws UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getBillingAddress()
    {
        throw new UnsupportedException("getBillingAddress is not supported for " . get_class($this));
    }

    /**
     * payment provider Token
     *
     * @throws UnsupportedException
     * @return string
     */
    public function getPaymentProvider()
    {
        throw new UnsupportedException("getPaymentProvider is not supported for " . get_class($this));
    }

    /**
     * Get OrderState
     *
     * @throws UnsupportedException
     * @return OrderState
     */
    public function getOrderState()
    {
        throw new UnsupportedException("getOrderStates is not supported for " . get_class($this));
    }
}