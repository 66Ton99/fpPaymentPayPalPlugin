<?php

/**
 * fpPaymentPayPalValidator Test Case
 *
 * @package    fpPayment
 * @subpackage Base
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalValidatorTestCase extends sfBasePhpunitTestCase
{
  
  /**
   * @test
   * @expectedException     sfValidatorError
   * @expectedExceptionCode invalid
   */
  public function clean_invalid()
  {
    $object = new fpPaymentPayPalValidator();
  
    $object->clean('2134');
  }
  
  // TODO add tests
}
