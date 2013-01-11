<?php

/*
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Craig Christenson
 * @package    Tco (2Checkout.com)
 * @copyright  Copyright (c) 2010 Craig Christenson
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Craig_Tco_RedirectController extends Mage_Core_Controller_Front_Action {

    public function getCheckout() {
    return Mage::getSingleton('checkout/session');
    }

    protected $order;

    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    public function indexAction() {
        $this->getResponse()
                ->setHeader('Content-type', 'text/html; charset=utf8')
                ->setBody($this->getLayout()
                ->createBlock('tco/redirect')
                ->toHtml());
    }

    public function successAction() {
        $post = $this->getRequest()->getPost();
        $insMessage = $this->getRequest()->getPost();
        foreach ($_REQUEST as $k => $v) {
            $v = htmlspecialchars($v);
            $v = stripslashes($v);
            $post[$k] = $v;
        }

        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($post['merchant_order_id']);
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $hashSecretWord = Mage::getStoreConfig('payment/tco/secret_word');
        $hashSid = Mage::getStoreConfig('payment/tco/sid');
        $hashTotal = number_format($order->getBaseGrandTotal(), 2, '.', '');

        if (Mage::getStoreConfig('payment/tco/demo') == '1') {
            $hashOrder = '1';
        } else {
            $hashOrder = $post['order_number'];
        }

        $StringToHash = strtoupper(md5($hashSecretWord . $hashSid . $hashOrder . $hashTotal));

        if ($StringToHash == $post['key']) {
            $this->_redirect('checkout/onepage/success');
            $order->sendNewOrderEmail();
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
            $order->setData('ext_order_id',$post['order_number'] );
            $order->save();
        } else {
            $this->_redirect('checkout/onepage/success');
            $order->addStatusHistoryComment($hashTotal);
            $order->addStatusHistoryComment('Hash did not match, check secret word.');
            $order->save();
        }
    }

}

?>
