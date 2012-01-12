<?php

/**
 * fpPaymentPayPalIpnExpress Test Case
 *
 * @package    fpPayment
 * @subpackage Base
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnExpressTestCase extends sfBasePhpunitTestCase 
{
  
  protected $options = array(
    'url' => 'api-3t.sandbox.paypal.com',
    'fields' => array(
      'METHOD' => 'SetExpressCheckout',
      'USER' => 'base_1326297340_biz_api1.66ton99.org.ua',
      'PWD' => '1326297384',
      'SIGNATURE' => 'AT3WsCEPtkm5hZOivSIR56i9mZ-pAMBq3bM0KHn8Eqv8cIi2mctzTOX-',
      'errorURL' => 'http://example.com/error',
      'returnURL' => 'http://example.com/success',
      'cancelURL' => 'http://example.com/cancelle',
      'callback' => 'http://example.com/callback',
      'Amt' => '10',
      'paymentType' => 'Authorization', // or 'Sale' or 'Order'
    )
  );
  
  /**
   * @test
   */
  public function construct_and_getData()
  {
    $obj = new fpPaymentPayPalIpnExpress($this->options);
    $this->assertEquals($obj->getData(), array(
        'METHOD' => 'SetExpressCheckout',
        'VERSION' => '56.0',
        'USER' => 'base_1326297340_biz_api1.66ton99.org.ua',
        'PWD' => '1326297384',
        'SIGNATURE' => 'AT3WsCEPtkm5hZOivSIR56i9mZ-pAMBq3bM0KHn8Eqv8cIi2mctzTOX-',
        'errorURL' => 'http://example.com/error',
        'returnURL' => 'http://example.com/success',
        'cancelURL' => 'http://example.com/cancelle',
        'callback' => 'http://example.com/callback',
        'callbackVersion' => '56.0',
        'Amt' => '10',
        'paymentType' => 'Authorization',
        'CURRENCYCODE' => 'USD'
      ));
  }
  
  /**
   * @depends construct_and_getData
   * @test
   */
  public function getToken()
  {
    $stub = $this->getMock('fpPaymentPayPalIpnExpress', array('getLoger'), array($this->options));

    $stub->expects($this->any())
         ->method('getLoger')
         ->will($this->returnValue(new fpPaymentTestNullObject()));
    if (false === $stub->getToken()) {
      $this->fail(print_r($stub->getResponse(), true));
    }
  }
}