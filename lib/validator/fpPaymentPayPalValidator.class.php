<?php

/**
 * PayPal account Validator
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalValidator extends sfValidatorEmail
{
  
  /**
   * (non-PHPdoc)
   * @see sfValidatorEmail::configure()
   */
  protected function configure($options = array(), $messages = array())
  {
    $this->addMessage('does_not_exist', 'Account doesn\'t exist');
    parent::configure($options, $messages);
  }
  
  /**
   * (non-PHPdoc)
   * @see sfValidatorRegex::doClean()
   */
  protected function doClean($value)
  {
    $clean = parent::doClean($value);
    
    if (!fpPaymentContext::getInstance()->getPayPal()->getIpn()->isVerifiedAccount($value))
    {
      throw new sfValidatorError($this, 'does_not_exist', array('value' => $value));
    }
    
    return $clean;
  }
}
