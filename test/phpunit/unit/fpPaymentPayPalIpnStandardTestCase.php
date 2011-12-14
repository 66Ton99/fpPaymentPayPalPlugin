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
    $obj = new fpPaymentPayPalIpnStandard();
    $this->assertEquals($obj->getData(), array(
        'amount_1' => 0,
        'item_name_1' => '',
        'quantity_1' => 0,
        '_info' => 'Array',
        'cmd' => '_cart',
        'upload' => 1,
        'shipping' => 0.00,
        'cancel_return' => 'http://./symfony/symfony/fpPaymentPayPal/cancelled',
        'notify_url' => 'http://./symfony/symfony/fpPaymentPayPal/callback',
        'return' => 'http://./symfony/symfony/fpPaymentPayPal/success',
        'redirect_cmd' => '_xclick',
        'currency_code' => '',
        'invoice' => 0,
        'business' => ''
      ));
  }
}