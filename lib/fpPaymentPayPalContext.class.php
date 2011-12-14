<?php

/**
 * PayPal Context
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalContext extends fpPaymentMethodContext
{
  
  const NAME = 'PayPal';
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentMethodContext::renderInfoPage()
   */
  public function renderInfoPage(sfAction &$action, sfRequest $request)
  {
    $action->redirect('@fpPaymentPlugin_orderReview');
    return sfView::NONE;
  }

  /**
   * (non-PHPdoc)
   * @see fpPaymentMethodContext::renderSuccessPage()
   */
  public function renderSuccessPage(sfAction &$action, sfRequest $request) {
    if ($this->getIpn()->getErrors()) {
      $action->redirect('@fpPaymentPayPalPlugin_error');
    } else {
      $action->redirect($this->getContext()->getPayPal()->getIpn()->getRedirectUrl());
    }
    return sfView::NONE;
  }
  
	/**
   * (non-PHPdoc)
   * @see fpPaymentMethodContext::renderErrorPage()
   */
  public function renderErrorPage(sfAction &$action, sfRequest $request)
  {
    $action->forward(sfConfig::get('fp_payment_paypal_page_error_module', 'fpPaymentPayPal'),
                     sfConfig::get('fp_payment_paypal_page_error_action', 'error'));
  }
}
