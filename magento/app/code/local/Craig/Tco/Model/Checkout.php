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

class Craig_Tco_Model_Checkout extends Mage_Payment_Model_Method_Abstract {

    protected $_code  = 'tco';
    protected $_paymentMethod = 'shared';

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('tco/redirect');
    }

    //get SID
    public function getSid() {
        $sid = $this->getConfigData('sid');
        return $sid;
    }

    //get Demo Setting
    public function getDemo() {
        if ($this->getConfigData('demo') == '1') {
            $demo = 'Y';
        } else {
            $demo = 'N';
        }
        return $demo;
    }

    //get Checkout Display
    public function getDisplay() {
        if ($this->getConfigData('inline') == '1') {
            $display = true;
        } else {
            $display = false;
        }
        return $display;
    }

    //get purchase routine URL
    public function getUrl() {
        $url = "https://www.2checkout.com/checkout/purchase";
        return $url;
    }

    //get checkout language
    public function getLanguage() {
        $lang = $this->getConfigData('checkout_language');
        return $lang;
    }

    //get custom checkout message
    public function getRedirectMessage() {
        $redirect_message = $this->getConfigData('redirect_message');
        return $redirect_message;
    }

    //get order
    public function getQuote() {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        return $order;
    }

    //get product data
    public function getProductData() {
        $products = array();
        $items = $this->getQuote()->getAllItems();
        if ($items) {
            $i = 1;
            foreach($items as $item){
                if ($item->getParentItem()) {
                    continue;
                }
                $products['c_name_'.$i] = $item->getName();
                $products['c_description_'.$i] = $item->getSku();
                $products['c_price_'.$i] = number_format($item->getPrice(), 2, '.', '');
                $products['c_prod_'.$i] = $item->getSku() . ',' . $item->getQtyToInvoice();
                $i++;
            }
        }
        return $products;
    }

    //get lineitem data
    public function getLineitemData() {
        $lineitems = array();
        $items = $this->getQuote()->getAllItems();
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $taxFull = $order->getFullTaxInfo();
        $ship_method   = $order->getShipping_description();
        $coupon = $order->getCoupon_code();
        $lineitem_total = 0;
        $i = 1;
        //get products
        if ($items) {
            foreach($items as $item){
                if ($item->getParentItem()) {
                    continue;
                }
                $lineitems['li_'.$i.'_type'] = 'product';
                $lineitems['li_'.$i.'_product_id'] = $item->getSku();
                $lineitems['li_'.$i.'_quantity'] = $item->getQtyToInvoice();
                $lineitems['li_'.$i.'_name'] = $item->getName();
                $lineitems['li_'.$i.'_description'] = $item->getDescription();
                $lineitems['li_'.$i.'_price'] = number_format($item->getPrice(), 2, '.', '');
                $lineitem_total += number_format($item->getPrice(), 2, '.', '');
                $i++;
            }
        }
        //get taxes
        if ($taxFull) {
            foreach($taxFull as $rate){
                $lineitems['li_'.$i.'_type'] = 'tax';
                $lineitems['li_'.$i.'_name'] = $rate['rates']['0']['code'];
                $lineitems['li_'.$i.'_price'] = round($rate['amount'], 2);
                $lineitem_total += round($rate['amount'], 2);
                $i++;
            }
        }
        //get shipping
        if ($ship_method) {
            $lineitems['li_'.$i.'_type'] = 'shipping';
            $lineitems['li_'.$i.'_name'] = $order->getShipping_description();
            $lineitems['li_'.$i.'_price'] = round($order->getShippingAmount(), 2);
            $lineitem_total += round($order->getShippingAmount(), 2);
            $i++;
        }
        //get coupons
        if ($coupon) {
            $lineitems['li_'.$i.'_type'] = 'coupon';
            $lineitems['li_'.$i.'_name'] = $order->getCoupon_code();
            $lineitems['li_'.$i.'_price'] = trim(round($order->getBase_discount_amount(), 2), '-');
            $lineitem_total -= trim(round($order->getBase_discount_amount(), 2), '-');
            $i++;
        }
        return $lineitems;
    }

    //check total
    public function checkTotal() {
        $items = $this->getQuote()->getAllItems();
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $taxFull = $order->getFullTaxInfo();
        $ship_method   = $order->getShipping_description();
        $coupon = $order->getCoupon_code();
        $lineitem_total = 0;
        $i = 1;
        //get products
        if ($items) {
            foreach($items as $item){
                if ($item->getParentItem()) {
                    continue;
                }
                $lineitem_total += number_format($item->getPrice(), 2, '.', '');
            }
        }
        //get taxes
        if ($taxFull) {
            foreach($taxFull as $rate){
                $lineitem_total += round($rate['amount'], 2);
            }
        }
        //get shipping
        if ($ship_method) {
            $lineitem_total += round($order->getShippingAmount(), 2);
        }
        //get coupons
        if ($coupon) {
            $lineitem_total -= trim(round($order->getBase_discount_amount(), 2), '-');
        }
        return $lineitem_total;
    }

    //get tax data
    public function getTaxData() {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $taxes = array();
        $taxFull = $order->getFullTaxInfo();
        if ($taxFull) {
            $i = 1;
            foreach($taxFull as $rate){
                $taxes['tax_id_'.$i] = $rate['rates']['0']['code'];
                $taxes['tax_amount_'.$i] = round($rate['amount'], 2);
                $i++;
            }
        }
        return $taxes;
    }

    //get HTML form data
    public function getFormFields() {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount   = round($order->getGrandTotal(), 2);
        $a = $this->getQuote()->getShippingAddress();
        $b = $this->getQuote()->getBillingAddress();
        $country = $b->getCountry();
        $currency_code = $order->getOrderCurrencyCode();
        $shipping = round($order->getShippingAmount(), 2);
        $weight = round($order->getWeight(), 2);
        $ship_method   = $order->getShipping_description();
        $tax = trim(round($order->getTaxAmount(), 2));
        $productData = $this->getProductData();
        $taxData = $this->getTaxData();
        $cart_order_id = $order_id;
        $lineitemData = $this->getLineitemData();

        $tcoFields = array();
        $tcoFields['sid']					= $this->getSid();
        $tcoFields['lang']                  = $this->getLanguage();
        $tcoFields['purchase_step']         = 'payment-method';
        $tcoFields['merchant_order_id']		= $order_id;
        $tcoFields['email']					= $order->getData('customer_email');
        $tcoFields['first_name']			= $b->getFirstname();
        $tcoFields['last_name']				= $b->getLastname();
        $tcoFields['phone']					= $b->getTelephone();
        $tcoFields['country']				= $b->getCountry();
        $tcoFields['street_address']		= $b->getStreet1();
        $tcoFields['street_address2']		= $b->getStreet2();
        $tcoFields['city']					= $b->getCity();

        if ($country == 'US' || $country == 'CA') {
            $tcoFields['state']             = $b->getRegion();
        } else {
            $tcoFields['state']				= 'XX';
        }

        $tcoFields['zip']					= $b->getPostcode();

        if ($a) {
            $tcoFields['ship_name']             = $a->getFirstname() . ' ' . $a->getLastname();
            $tcoFields['ship_country']			= $a->getCountry();
            $tcoFields['ship_street_address']	= $a->getStreet1();
            $tcoFields['ship_street_address2']	= $a->getStreet2();
            $tcoFields['ship_city']				= $a->getCity();
            $tcoFields['ship_state']			= $a->getRegion();
            $tcoFields['ship_zip']				= $a->getPostcode();
            $tcoFields['sh_cost']				= $shipping;
            $tcoFields['sh_weight']				= $weight;
            $tcoFields['ship_method']			= $ship_method;
        }
        $tcoFields['2co_tax']				= $tax;
        $tcoFields['2co_cart_type']			= 'magento';
        $tcoFields['x_Receipt_Link_URL']    = Mage::getUrl('tco/redirect/success', array('_secure' => true));
        $tcoFields['return_url'] = Mage::getUrl('tco/redirect/cart', array('_secure' => true));
        $tcoFields['demo']					= $this->getDemo();
        $tcoFields['currency_code'] = $currency_code;

        //Check Integration mode
        $lineitem_total = $this->checkTotal();
        if ($lineitem_total != $amount) {
            $tcoFields['id_type']			= '1';
            $tcoFields['total']				= $amount;
            $tcoFields['cart_order_id']			= $order_id;
            $result = $productData + $taxData + $tcoFields;
        } else {
            $tcoFields['mode']		        	= '2CO';
            $result = $tcoFields + $lineitemData;
        }

        return $result;
    }

}
