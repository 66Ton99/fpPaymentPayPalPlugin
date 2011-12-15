<?php

/**
 *
 * @package    fpPayment
 * @subpackage Base
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnAdaptiveTestCase extends sfBasePhpunitTestCase 
{
  
  protected $options = array(
    'url' => 'svcs.sandbox.paypal.com',
    'url_path' => '/AdaptivePayments/Pay',
    'headers' => array(
      'X-PAYPAL-SECURITY-USERID' => 'leftco_1317812970_biz_api1.66ton99.org.ua',
      'X-PAYPAL-SECURITY-SIGNATURE' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31A.Nlbkq0n7eMOKw24aijdEK5mhPA',
      'X-PAYPAL-SECURITY-PASSWORD' => '1317813005',
      'X-PAYPAL-APPLICATION-ID' => 'APP-80W284485P519543T',
      'X-PAYPAL-DEVICE-IPADDRESS' => '82.117.234.33',
    ),
    'fields' => array(
      'returnUrl' => 'http://payment.tonpc.forma-dev.com/success',
      'cancelUrl' => 'http://payment.tonpc.forma-dev.com/cancelled',
      'ipnNotificationUrl' => 'http://payment.tonpc.forma-dev.com/callback',
      'actionType' => 'PAY',
      'receiverList.receiver(0).email' => 'leftco_1317812970_biz@66ton99.org.ua',
      'receiverList.receiver(0).amount' => '100'
    )
  );
  
  /**
   * @test
   */
  public function construct_and_getData()
  {
    
    $obj = new fpPaymentPayPalIpnAdaptive($this->options);
    $this->assertEquals($obj->getData(), array(
        'errorUrl' => 'http://./symfony/symfony/fpPaymentPayPal/error',
        'returnUrl' => 'http://payment.tonpc.forma-dev.com/success',
        'cancelUrl' => 'http://payment.tonpc.forma-dev.com/cancelled',
        'ipnNotificationUrl' => 'http://payment.tonpc.forma-dev.com/callback',
        'requestEnvelope.errorLanguage' => 'en_US',
        'currencyCode' => 'USD',
        'actionType' => 'PAY',
        'receiverList.receiver(0).email' => 'leftco_1317812970_biz@66ton99.org.ua',
        'receiverList.receiver(0).amount' => '100',
      ));
  }
  
  /**
   * @test
   */
  public function getToken()
  {
    $stub = $this->getMock('fpPaymentPayPalIpnAdaptive', array('getLoger', 'getOrderId'), array($this->options));

    $stub->expects($this->any())
         ->method('getLoger')
         ->will($this->returnValue(new fpPaymentTestNullObject()));
    $stub->expects($this->any())
         ->method('getOrderId')
         ->will($this->returnValue(time()));
    if (false == $stub->getToken()) {
      $this->fail(print_r($stub->getResponse(), true));
    }
  }
}