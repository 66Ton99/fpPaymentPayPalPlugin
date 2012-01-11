<?php

/**
 * PayPal base IPN
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
abstract class fpPaymentPayPalIpnBase extends fpPaymentIpnBase
{
  
  protected $redirectUrl;
  
  /**
   * Retrun redirect url to PayPal
   *
   * @return string
   */
  public function getRedirectUrl()
  {
    return $this->redirectUrl;
  }
  
  /**
   * Get current orderid
   * 
   * @todo remove
   *
   * @return int
   */
  protected function getOrderId()
  {
    return $this->getContext()->getOrderModel()->getId();
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentIpnBase::getLoger()
   */
  public function getLoger()
  {
    return $this->getContext()->getPayPal()->getLoger();
  }
  
  /**
   * Process callback data
   *
   * @param string $data
   *
   * @return array
   */
  public function processCallback($data)
  {
    $params = $this->getProtocol()->toArray($data);
    $this->getLoger()->addArray($params, 'Callback PARAMS');
    
    $order = $this->getContext()->getOrderModel();
    if (empty($order)) {
      $this->getLoger()->add("FAIL order with don't find", 'CALLBACK');
      return false;
    }
    if (fpPaymentPaypal::NAME != $order->getType()) {
      $this->getLoger()->add('FRAUD', 'CALLBACK');
      return false;
    }
  
    unset(
      $params['module'],
      $params['action'],
      $params['orderId']
    );
    
  
    $this->setData($params);
    $this->processNotifyValidate();
    $order->setStatus($this->isVerified()?fpPaymentOrderStatusEnum::SUCCESS:fpPaymentOrderStatusEnum::FAIL);
    $order->save();
    
  
//     $paypalModel->setResponse($this->getResponse());
//     $paypalModel->save();
    return true;
  }
  
  /**
   * Checks come data
   *
   * @param array $params
   *
   * @return fpPaymentPaypalIpn
   */
  public function processNotifyValidate()
  {
    $url = 'https://' . sfConfig::get('fp_payment_paypal_ipn_url', 'www.paypal.com') .
      sfConfig::get('fp_payment_paypal_ipn_url_path', '/cgi-bin/webscr?');
    $data = $this->getData();
    if (!empty($data['cmd'])) {
      $data['cmd'] = '_notify-validate';
    } else {
      $data = array_merge(array('cmd' => '_notify-validate'), $data);
    }
    
    $connectionn = new fpPaymentConnection($url);
    $dataString = $this->getProtocol()->fromArray((array)$data);
    $this->getLoger()
      ->addArray($data, 'Send notify data to ' . $url . $dataString);
  
    $this->response = $connectionn->sendPostRequest($dataString);
    $this->getLoger()
      ->add($this->response, 'Get notify data');
    return $this;
  }
  
  /**
   * Returns true if paypal says the order is good, false if not
   *
   * @return bool
   */
  public function isVerified()
  {
    return (0 == strcmp('VERIFIED', $this->response));
  }
  
  // TODO finish
//   /**
//    * Save log to DB
//    *
//    * @param array $data
//    * @param int $orderId
//    *
//    * @return void
//    */
//   protected function saveDbLog($data, $orderId)
//   {
//     $paypalModel = new fpPaymentPaypal();
//     $paypalModel->setOrderId($orderId);
//     $paypalModel->setCallback($data);
//     $paypalModel->save();
//   }
}