<?php

class Craig_Tco_Model_Observer {

    public function issue_creditmemo_refund(Varien_Object $payment) {

        $refund = Mage::getStoreConfig('payment/tco/refund');

        if ($refund == '1') {

        $creditmemo = $payment->getCreditmemo()->getOrder()->getData();
        $creditmemo_amount = $payment->getCreditmemo()->getData();
        $creditmemo_comment = $payment->getCreditmemo()->getCommentsCollection()->toArray();

        if(isset($creditmemo_comment['items'][0]['comment'])){
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
            'Accept: application/xml'
        );

        $url = 'https://www.2checkout.com/api/sales/refund_invoice';

            $http = new Varien_Http_Adapter_Curl();
            $http->write('POST', $url, '1.1', $headers, $data);
            $response = $http->read();
        }
    }
}
?>
