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
   * Process callback data
   *
   * @param array $data
   *
   * @return array
   */
  abstract public function processCallback($data);
  
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
}