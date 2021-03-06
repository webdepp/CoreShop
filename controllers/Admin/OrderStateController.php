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

use CoreShop\Model\Order\State;
use CoreShop\Controller\Action\Admin;

/**
 * Class CoreShop_Admin_OrderStateController
 */
class CoreShop_Admin_OrderStateController extends Admin
{
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array('list');
        if (!in_array($this->getParam('action'), $notRestrictedActions)) {
            $this->checkPermission('coreshop_permission_order_states');
        }
    }

    public function listAction()
    {
        $list = State::getList();

        $data = array();
        if (is_array($list->getData())) {
            foreach ($list->getData() as $orderState) {
                $data[] = $this->getTreeNodeConfig($orderState);
            }
        }
        $this->_helper->json($data);
    }

    protected function getTreeNodeConfig(State $orderState)
    {
        $tmp = array(
            'id' => $orderState->getId(),
            'text' => $orderState->getName(),
            'qtipCfg' => array(
                'title' => 'ID: '.$orderState->getId(),
            ),
            'name' => $orderState->getName(),
            'color' => $orderState->getColor(),
            'email' => $orderState->getEmail()
        );

        return $tmp;
    }

    public function addAction()
    {
        $name = $this->getParam('name');

        if (strlen($name) <= 0) {
            $this->helper->json(array('success' => false, 'message' => $this->getTranslator()->translate('Name must be set')));
        } else {
            $orderState = new State();
            $orderState->setName($name);
            $orderState->setAccepted(0);
            $orderState->setShipped(0);
            $orderState->setEmail(0);
            $orderState->setPaid(0);
            $orderState->setInvoice(0);
            $orderState->setColor("#FFFFFF");
            $orderState->save();

            $this->_helper->json(array('success' => true, 'data' => $orderState));
        }
    }

    public function getAction()
    {
        $id = $this->getParam('id');
        $orderState = State::getById($id);

        if ($orderState instanceof State) {
            $this->_helper->json(array('success' => true, 'data' => $orderState->getObjectVars()));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function saveAction()
    {
        $id = $this->getParam('id');
        $data = $this->getParam('data');
        $oderState = State::getById($id);

        if ($data && $oderState instanceof State) {
            $data = \Zend_Json::decode($this->getParam('data'));

            $oderState->setValues($data);
            $oderState->save();

            $this->_helper->json(array('success' => true, 'data' => $oderState->getObjectVars()));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function deleteAction()
    {
        $id = $this->getParam('id');
        $oderState = State::getById($id);

        if ($oderState instanceof State) {
            $oderState->delete();

            $this->_helper->json(array('success' => true));
        }

        $this->_helper->json(array('success' => false));
    }
}
