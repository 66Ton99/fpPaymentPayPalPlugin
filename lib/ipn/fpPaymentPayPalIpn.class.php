<?php

/**
 * Ipn decorator
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 * 
 * @property $object fpPaymentIpnBase
 * 
 * @method fpPaymentPayPalIpn process()
 * @method fpPaymentPayPalIpn setData()
 * @method fpPaymentPayPalIpn addData()
 * @method array getData()
 * @method bool hasErrors()
 * @method array getErrors()
 * @method mixed getResponse()
 */
class fpPaymentPayPalIpn extends fpPaymentDecoratorBase
{
	
	/**
   * Constructor
   * 
   * @param fpPaymentProtocolBase $object
   */
  function __construct(fpPaymentIpnBase $ipn)
  {
    $this->object = $ipn;
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentDecoratorBase::__call()
   */
  public function __call($method, $params)
  {
    $return = parent::__call($method, $params);
    if ($return instanceof fpPaymentIpnBase) {
      return $this;
    }
    return $return;
  }
}