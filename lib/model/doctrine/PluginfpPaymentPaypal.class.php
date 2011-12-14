<?php

/**
 * PluginfpPaymentPaypal
 *  
 * @package    fpPayment
 * @subpackage PayPal
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
abstract class PluginfpPaymentPaypal extends BasefpPaymentPaypal
{
  const NAME = 'PayPal';
  
  /**
   * (non-PHPdoc)
   * @see BasefpPaymentPaypal::setResponse()
   * 
   * @param array $arr
   * 
   * return void
   */
  public function setCallback($data)
  {
    $responseString = '';
    $responseArray = array();
    if (is_array($data)) {
      $responseString = print_r($data, true);
      $responseArray = $data;
    } else {
      $responseString = $data;
      $responseArray = array(); // TODO Implement
    }
    
    if (!empty($responseArray)) {
      foreach ($data as $field => $val) {
        if ($this->getTable()->hasField($field)) {
          $this->_set($field, $val);
        }
      }
    }
    parent::_set('callback', $responseString);
  }
}