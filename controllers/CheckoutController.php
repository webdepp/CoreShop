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

use CoreShop\Controller\Action;
use CoreShop\Model\Plugin\Payment;

/**
 * Class CoreShop_CheckoutController
 */
class CoreShop_CheckoutController extends Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        $allowedActions = array('confirmation');

        //Checkout is not allowed in CatalogMode
        if (\CoreShop\Model\Configuration::isCatalogMode()) {
            $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language), 'coreshop_index', true));
        }

        if (count($this->view->cart->getItems()) == 0 && !in_array($this->getParam('action'), $allowedActions)) {
            $this->redirect(\CoreShop::getTools()->url(array('act' => 'list'), 'coreshop_cart', true));
        }

        if (!is_array($this->session->order)) {
            $this->session->order = array();
        }

        $this->prepareCart();
    }

    public function indexAction()
    {
        $user = CoreShop::getTools()->getUser();

        if ($user instanceof \CoreShop\Model\User) {
            $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'address'), 'coreshop_checkout'));
        }

        if ($this->getParam('error')) {
            $this->view->error = $this->getParam('error');
        }

        $this->view->message = $this->getParam('message');

        $this->view->headTitle($this->view->translate('Checkout'));

        \CoreShop\Tracking\TrackingManager::getInstance()->trackCheckout($this->cart, 2);
    }

    public function registerAction()
    {
        $this->view->redirect = \CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'address'), 'coreshop_checkout');

        $this->_helper->viewRenderer('user/register', null, true);
    }

    public function addressAction()
    {
        $this->checkIsAllowed();

        if ($this->getRequest()->isPost()) {
            $shippingAddress = $this->getParam('shipping-address');
            $billingAddress = $this->getParam('billing-address');

            if ($this->getParam('useShippingAsBilling', 'off') == 'on') {
                $billingAddress = $this->getParam('shipping-address');
            }

            $this->cart->setShippingAddress(\CoreShop\Model\User\Address::getById($shippingAddress));
            $this->cart->setBillingAddress(\CoreShop\Model\User\Address::getById($billingAddress));
            $this->cart->save();

            //Reset Country in Session, now we use BillingAddressCountry
            unset($this->session->countryId);

            $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'shipping'), 'coreshop_checkout'));
        }

        \CoreShop\Tracking\TrackingManager::getInstance()->trackCheckout($this->cart, 3);
        $this->view->headTitle($this->view->translate('Address'));
    }

    public function shippingAction()
    {
        $this->checkIsAllowed();

        $this->view->message = $this->getParam('message');

        //Download Article - no need for Shipping
        if (!$this->cart->hasPhysicalItems()) {
            $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'payment'), 'coreshop_checkout'));
        }

        $this->view->carriers = \CoreShop\Model\Carrier::getCarriersForCart($this->cart);

        if ($this->getRequest()->isPost()) {
            if (!$this->getParam('termsAndConditions', false)) {
                $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'shipping', 'message' => 'Please check terms and conditions'), 'coreshop_checkout'));
            }

            $carrier = $this->getParam('carrier', false);

            foreach ($this->view->carriers as $c) {
                if ($c->getId() == $carrier) {
                    $carrier = $c;
                    break;
                }
            }

            if (!$carrier instanceof \CoreShop\Model\Carrier) {
                $this->view->error = 'oh shit, not found';
            } else {
                $this->cart->setCarrier($carrier);
                $this->cart->setPaymentModule(null); //Reset PaymentModule, payment could not be available for this carrier
                $this->cart->save();

                $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'payment'), 'coreshop_checkout'));
            }
        }

        \CoreShop\Tracking\TrackingManager::getInstance()->trackCheckout($this->cart, 4);
        $this->view->headTitle($this->view->translate('Shipping'));
    }

    public function paymentAction()
    {
        $this->checkIsAllowed();

        $this->view->provider = \CoreShop::getPaymentProviders($this->cart);

        if ($this->getRequest()->isPost()) {
            $paymentProvider = $this->getParam('payment_provider', array());
            $provider = null;

            foreach ($this->view->provider as $provider) {
                if ($provider->getIdentifier() == $paymentProvider) {
                    $paymentProvider = $provider;
                    break;
                }
            }

            if (!$paymentProvider instanceof Payment) {
                $this->view->error = 'oh shit, not found';
            } else {
                $this->cart->setPaymentModule($paymentProvider->getIdentifier());
                $this->cart->save();

                $this->redirect($paymentProvider->process($this->cart));
            }
        }

        \CoreShop\Tracking\TrackingManager::getInstance()->trackCheckout($this->cart, 5);
        $this->view->headTitle($this->view->translate('Payment'));
    }

    public function validateAction()
    {
        $this->view->headTitle($this->view->translate('Validate'));

        $paymentViewScript = $this->getParam("paymentViewScript");

        $this->view->paymentViewScript = $paymentViewScript;

        \CoreShop\Tracking\TrackingManager::getInstance()->trackCheckoutAction($this->cart, 6);
    }

    public function confirmationAction()
    {
        $this->view->headTitle($this->view->translate('Confirmation'));

        $order = $this->getParam("order");
        $paymentViewScript = $this->getParam("paymentViewScript");

        $this->prepareCart();
        //$this->cart->delete(); //Keep Cart for Statistics Purpose

        if (!$order instanceof \CoreShop\Model\Order) {
            $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language), 'coreshop_index'));
        }

        $this->view->order = $order;
        $this->view->paymentViewScript = $paymentViewScript;

        unset($this->session->order);
        unset($this->session->cart);
        unset($this->session->cartId);

        if (CoreShop::getTools()->getUser()->getIsGuest()) {
            \CoreShop::getTools()->unsetUser();
        }

        \CoreShop\Tracking\TrackingManager::getInstance()->trackCheckoutComplete($order);
    }

    public function errorAction()
    {
        $this->view->error = $this->getParam("error");
        $this->view->headTitle("Payment Error");
    }

    public function canceledAction()
    {
        $this->view->headTitle("Payment Canceled");
    }

    protected function checkIsAllowed()
    {
        if (!\CoreShop::getTools()->getUser() instanceof \CoreShop\Model\User) {
            $this->redirect(\CoreShop::getTools()->url(array('lang' => $this->view->language, 'act' => 'index'), 'coreshop_checkout'));
            exit;
        }
    }
}
