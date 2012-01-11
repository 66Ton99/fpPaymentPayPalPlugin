<?php

/**
 * fpPaymentPayPalIpnStandard test case
 *
 * @package    fpPayment
 * @subpackage Base
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnStandardTestCase extends sfBasePhpunitTestCase 
{
  
  /**
   * @test
   */
  public function construct_and_getData()
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
    $obj = new fpPaymentPayPalIpnStandard();
    $data = $obj->getData();
    $baseUrl = substr($data['cancel_return'], 0, -9);
    $this->assertEquals($data, array(
        'amount_1' => 0,
        'item_name_1' => '',
        'quantity_1' => 0,
        '_info' => 'Array',
        'cmd' => '_cart',
        'upload' => 1,
        'shipping' => 0.00,
        'cancel_return' => $baseUrl . 'cancelled',
        'notify_url' => $baseUrl . 'callback',
        'return' => $baseUrl . 'success',
        'redirect_cmd' => '_xclick',
        'currency_code' => '',
        'invoice' => 0,
        'business' => ''
      ));
  }
}