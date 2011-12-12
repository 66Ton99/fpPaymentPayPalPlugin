<?php

/**
 * fpPaymentPayPalPlugin configuration
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author 	   Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalPluginConfiguration extends sfPluginConfiguration
{
  public static $reg = false;
  
  /**
   * (non-PHPdoc)
   * @see sfPluginConfiguration::setup()
   */
  public function setup()
  {
    if (!self::$reg) {
      self::$reg = true;
      $this->dispatcher->connect('context.load_factories', array($this, 'listenToContextLoadFactories'));
    }
  }
  
  /**
   * Enter description here ...
   *
   * @param sfEvent $event
   *
   * @return
   */
  public function listenToContextLoadFactories(sfEvent $event)
  {
    //$context = $event->getSubject();
    $configFiles = $this->configuration->getConfigPaths('config/fp_payment_paypal.yml');
    $functionsClassName = sfConfig::get('functions_class_name');
    $functionsClassName::addConfigsToSystem("fp_payment_paypal_{$name}",
                                            sfDefineEnvironmentConfigHandler::getConfiguration($configFiles));
    fpPaymentContext::getInstance()->addPaymentMethod(array(fpPaymentPayPalContext::NAME => fpPaymentPayPalContext::NAME));
    
  }
  
}