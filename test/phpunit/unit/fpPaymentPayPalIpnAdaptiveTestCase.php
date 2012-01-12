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
      'X-PAYPAL-SECURITY-USERID' => 'base_1326297340_biz_api1.66ton99.org.ua',
      'X-PAYPAL-SECURITY-SIGNATURE' => 'AT3WsCEPtkm5hZOivSIR56i9mZ-pAMBq3bM0KHn8Eqv8cIi2mctzTOX-',
      'X-PAYPAL-SECURITY-PASSWORD' => '1326297384',
      'X-PAYPAL-APPLICATION-ID' => 'APP-80W284485P519543T',
      'X-PAYPAL-SANDBOX-EMAIL-ADDRESS' => 'test@66ton99.org.ua',
      'X-PAYPAL-DEVICE-IPADDRESS' => '127.0.0.1',
    ),
    'fields' => array(
      'errorUrl' => 'http://example.com/error',
      'returnUrl' => 'http://example.com/success',
      'cancelUrl' => 'http://example.com/cancelled',
      'ipnNotificationUrl' => 'http://example.com/callback',
      'actionType' => 'PAY',
      'receiverList.receiver(0).email' => 'base_1326297340_biz@66ton99.org.ua',
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
        'errorUrl' => 'http://example.com/error',
        'returnUrl' => 'http://example.com/success',
        'cancelUrl' => 'http://example.com/cancelled',
        'ipnNotificationUrl' => 'http://example.com/callback',
        'requestEnvelope.errorLanguage' => 'en_US',
        'currencyCode' => 'USD',
        'actionType' => 'PAY',
        'receiverList.receiver(0).email' => 'base_1326297340_biz@66ton99.org.ua',
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
      $this->fail(print_r($stub->getResponse(), true) . "\n Errors: " . print_r($stub->getErrors(), true));
    }
  }

  /**
   * @test
   */
  public function callback()
  {
    sfConfig::set('fp_payment_paypal_ipn_url', 'sandbox.paypal.com');
    $data = 'payment_request_date=Thu+Jan+05+06%3A43%3A04+PST+2012&return_url=https%3A//payment.tonpc.forma-dev.com/frontend_test.php/fpPaymentPayPal/success%3ForderId%3D1&fees_payer=EACHRECEIVER&ipn_notification_url=https%3A//payment.tonpc.forma-dev.com/frontend_test.php/fpPaymentPayPal/callback%3ForderId%3D1&sender_email=reach_1325697823_per%4066ton99.org.ua&verify_sign=AY4VRGelUH2AL64ek10J4VIp4l4BAoph2nUKX7wbl7PnV7M73eVb2VSN&test_ipn=1&transaction%5B0%5D.id_for_sender_txn=5UK59831YK611411R&transaction%5B0%5D.receiver=seler1_1325697721_biz%4066ton99.org.ua&cancel_url=https%3A//payment.tonpc.forma-dev.com/frontend_test.php/fpPaymentPayPal/cancelled%3ForderId%3D1&transaction%5B0%5D.is_primary_receiver=false&pay_key=AP-43531652CU3953123&action_type=PAY&transaction%5B0%5D.id=67A82457AS2732549&transaction%5B0%5D.status=Completed&transaction%5B0%5D.paymentType=SERVICE&transaction%5B0%5D.status_for_sender_txn=Completed&transaction%5B0%5D.pending_reason=NONE&transaction_type=Adaptive+Payment+PAY&transaction%5B0%5D.amount=USD+110.00&status=COMPLETED&log_default_shipping_address_in_transaction=false&charset=windows-1252&notify_version=UNVERSIONED&reverse_all_parallel_payments_on_error=false';
    $data .= '&orderId=1';
    $stub = $this->getMock('fpPaymentPayPalIpnAdaptive', array('getLoger', 'getOrderId'), array($this->options));

    $stub->expects($this->any())
         ->method('getLoger')
         ->will($this->returnValue(new fpPaymentTestNullObject()));
    $stub->expects($this->any())
         ->method('getOrderId')
         ->will($this->returnValue(1));
    
    $order = $this->getMock('fpPaymentOrder', array('getType', 'save'), array($this->options));
    $order->expects($this->any())
      ->method('getType')
      ->will($this->returnValue(fpPaymentPaypal::NAME));
    
    fpPaymentContext::getInstance()->setOrderModel($order);
    $this->assertTrue($stub->processCallback($data));
    $this->assertTrue($stub->isVerified());
  }
  
  /**
   * @test
   */
  public function isVerifiedAccount()
  {
    $stub = $this->getMock('fpPaymentPayPalIpnAdaptive', array('getLoger'), array($this->options));
    
    $stub->expects($this->any())
      ->method('getLoger')
      ->will($this->returnValue(new fpPaymentTestNullObject()));
    if (false == $stub->isVerifiedAccount('base_1326297340_biz@66ton99.org.ua')) {
      $this->fail(print_r($stub->getResponse(), true) . "\n Errors: " . print_r($stub->getErrors(), true));
    }
  }
}