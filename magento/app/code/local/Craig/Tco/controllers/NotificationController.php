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

class Craig_Tco_NotificationController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        if (!$this->getRequest()->isPost()) {
            return;
            $insMessage = $this->getRequest()->getPost();
        }
    }


    public function insAction() {
        $insMessage = $this->getRequest()->getPost();
        foreach ($_REQUEST as $k => $v) {
        $v = htmlspecialchars($v);
        $v = stripslashes($v);
        $insMessage[$k] = $v;
        }

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($insMessage['vendor_order_id']);
        $invoice_on_fraud = Mage::getStoreConfig('payment/tco/invoice_on_fraud');
        $invoice_on_order = Mage::getStoreConfig('payment/tco/invoice_on_order');
        $hashSecretWord = Mage::getStoreConfig('payment/tco/secret_word');
        $hashSid = $insMessage['vendor_id'];
        $hashOrder = $insMessage['sale_id'];
        $hashInvoice = $insMessage['invoice_id'];
        $StringToHash = strtoupper(md5($hashOrder . $hashSid . $hashInvoice . $hashSecretWord));

        if ($StringToHash != $insMessage['md5_hash'] && number_format($order->getGrandTotal(), 2, '.', '') != $insMessage['invoice_list_amount']) {
            $order->addStatusHistoryComment('Hash or total did not match!');
            $order->save();
            die('Hash Incorrect');
        } else {
            if ($insMessage['message_type'] == 'FRAUD_STATUS_CHANGED') {
                if ($insMessage['fraud_status'] == 'fail') {
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->addStatusHistoryComment('Order failed fraud review.')->save();
                } else if ($insMessage['fraud_status'] == 'pass') {
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->addStatusHistoryComment('Order passed fraud review.')->save();
                    if ($invoice_on_fraud == '1') {
                        try {
                            if(!$order->canInvoice()) {
                                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                            }
                            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                            if (!$invoice->getTotalQty()) {
                                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                            }
                            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                            $invoice->register();
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                            $transactionSave->save();
                        } catch (Mage_Core_Exception $e) {
                            echo $e;
                        }
                    }
                } else if ($insMessage['fraud_status'] == 'wait') {
                    $order->addStatusHistoryComment('Order undergoing additional fraud investigation.');
                    $order->save();
                }
            } else if ($insMessage['message_type'] == 'ORDER_CREATED') {
                if ($invoice_on_order == '1') {
                    try {
                        if(!$order->canInvoice()) {
                            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                        }
                        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                        if (!$invoice->getTotalQty()) {
                            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                        }

                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                        $invoice->register();
                        $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $transactionSave->save();
                    } catch (Mage_Core_Exception $e) {
                        echo $e;
                    }
                }
            }
        }
    }
}

?>
