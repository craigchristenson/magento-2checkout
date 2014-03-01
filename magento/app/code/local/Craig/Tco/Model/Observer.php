<?php

class Craig_Tco_Model_Observer extends Mage_Core_Block_Abstract {

    public function issue_creditmemo_refund(Varien_Object $payment) {

        $refund = Mage::getStoreConfig('payment/tco/refund');

        if ($refund == '1') {
            $order = $payment->getCreditmemo()->getOrder();
            $creditmemo = $payment->getCreditmemo()->getOrder()->getData();
            $creditmemo_amount = $payment->getCreditmemo()->getData();
            $creditmemo_comment = $payment->getCreditmemo()->getCommentsCollection()->toArray();

            if(isset($creditmemo_comment['items'][0]['comment'])) {
                $comment = $creditmemo_comment['items'][0]['comment'];
            } else {
                $comment = 'Refund issued by seller';
            }

            $username = Mage::getStoreConfig('payment/tco/username');
            $password = Mage::getStoreConfig('payment/tco/password');
            $auth = 'Basic ' . base64_encode($username . ':' . $password);

            $data = array();
            $data['sale_id'] = $creditmemo['ext_order_id'];
            $data['comment'] = $comment;
            $data['category'] = '5';
            $data['amount'] = $creditmemo_amount['grand_total'];
            $data['currency'] = 'vendor';

            $headers = array(
                'Authorization: ' . $auth,
                'Accept: application/json'
            );

            $url = 'https://www.2checkout.com/api/sales/refund_invoice';

            $config = array(
                'timeout'    => 30
            );

            try {
                $http = new Varien_Http_Adapter_Curl();
                $http->setConfig($config);
                $http->write(Zend_Http_Client::POST, $url, '1.1', $headers, $data);
                $response = $http->read();
                $order->addStatusHistoryComment($response);
                $order->save();
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('core')->__($e->getMessage()));
            }
        }
    }


    public function output_tco_redirect(Varien_Object $observer) {
        if (isset($_POST['payment']['method']) && $_POST['payment']['method'] == "tco") {
            $controller = $observer->getEvent()->getData('controller_action');
            $result = Mage::helper('core')->jsonDecode(
                $controller->getResponse()->getBody('default'),
                Zend_Json::TYPE_ARRAY
            );

            if (Mage::getStoreConfig('payment/tco/inline') == '1') {
                $js = '<script>
                    document.getElementById("review-please-wait").style["display"] = "block";
                    if ($$("a.top-link-cart")) {
                        $$("a.top-link-cart")[0].href = "'.Mage::getUrl('tco/redirect/cart', array('_secure' => true)).'";
                    }
                    if ($$("p.f-left").length !== 0) {
                        $$("p.f-left")[0].style["display"] = "none";
                    }
                    function formSubmit() {
                        $("tcosubmit").click();
                    }
                    var tcohead = $$("head")[0];
                    var tcoscript = new Element("script", { type: "text/javascript", src: "https://www.2checkout.com/static/checkout/javascript/direct.min.js" });
                    tcohead.appendChild(tcoscript);
                    var checkoutOrderBtn = $$("button.btn-checkout");
                    checkoutOrderBtn[0].removeAttribute("onclick");
                    checkoutOrderBtn[0].addEventListener("click", formSubmit, false);
                    new PeriodicalExecuter(function(pe) {
                        if (typeof window["inline_2Checkout"] != "undefined")
                        {
                            formSubmit();
                            pe.stop();
                        }
                    }, 0.50);
                </script>';
            } else {
                $js = '<script>
                    document.getElementById("review-please-wait").style["display"] = "block";
                    if ($$("a.top-link-cart")) {
                        $$("a.top-link-cart")[0].href = "'.Mage::getUrl('tco/redirect/cart', array('_secure' => true)).'";
                    }
                    if ($$("p.f-left").length !== 0) {
                        $$("p.f-left")[0].style["display"] = "none";
                    }
                    function formSubmit() {
                        $("tcosubmit").click();
                    }
                    var checkoutOrderBtn = $$("button.btn-checkout");
                    checkoutOrderBtn[0].removeAttribute("onclick");
                    checkoutOrderBtn[0].addEventListener("click", formSubmit, false);
                    formSubmit();
                </script>';
            }

            if (empty($result['error'])) {
                $controller->loadLayout('checkout_onepage_review');
                $html = $controller->getLayout()->createBlock('tco/redirect')->toHtml();
                $html .= $js;
                $result['update_section'] = array(
                    'name' => 'tcoiframe',
                    'html' => $html
                );
                $result['redirect'] = false;
                $result['success'] = false;
                $controller->getResponse()->clearHeader('Location');
                $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        }
        return $this;
    }

}
?>
