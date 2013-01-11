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



class Craig_Tco_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $tco = Mage::getModel('tco/checkout');

        $form = new Varien_Data_Form();
        $form->setAction($tco->getUrl())
            ->setId('pay')
            ->setName('pay')
            ->setMethod('POST')
            ->setUseContainer(true);
        $tco->getFormFields();
        foreach ($tco->getFormFields() as $field=>$value) {
           $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value, 'size'=>200));
        }

        $html = '<html><body>';
        $html.= $tco->getRedirectMessage();
        $html.= $form->toHtml();
        $html.= '<br>';
        $html.= '<script type="text/javascript">document.getElementById("pay").submit();</script>';
        $html.= '</body></html>';


        return $html;
    }
}

?>
