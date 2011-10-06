<?php

/**
 * PayPalForm
 * 
 * @package    fpPayment
 * @subpackage PayPal
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalRequestForm extends BaseCustomForm
{
  
  /**
   * (non-PHPdoc)
   * @see sfForm::setup()
   */
  public function setup()
  {
    $widgets = array();
    $fields = fpPaymentContext::getInstance()->getPayPal()->getIpn()->getData();
    $fields = new ArrayObject($fields);
    $this->getContext()->getDispatcher()->notify(new sfEvent($this, 'fp_payment.befor_process', array(
      'context' => fpPaymentContext::getInstance(),
      'values' => $fields,
    )));
    foreach ($fields as $key => $def) {
      $widgets[$key] = new sfWidgetFormInputHidden(array('default' => $def));
    }
    $this->setWidgets($widgets);
  }
  
  /**
   * Do Nothing
   * @see BaseCustomForm::configure()
   */
  public function configure()
  {}
}