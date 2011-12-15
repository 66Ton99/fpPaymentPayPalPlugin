<?php

/**
 * PayPal actions.
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author     Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalActionsBase extends sfActions
{
  
  /**
   * Success
   *
   * @param sfWebRequest $request
   *
   * @return void
   */
  public function executeSuccess(sfWebRequest $request)
  {
    fpPaymentContext::getInstance()->getPayPal()->getLoger()->addArray($request->getParameterHolder()->getAll(), 'Success');
  }
  
  /**
   * Cancelled
   *
   * @param sfWebRequest $request
   *
   * @return void
   */
  public function executeCancelled(sfWebRequest $request)
  {
    fpPaymentContext::getInstance()->getPayPal()->getLoger()->addArray($request->getParameterHolder()->getAll(), 'Cancelled');
    $id = (int)$request->getParameter('orderId');
    $order = fpPaymentOrderTable::getInstance()->findOneByIdAndStatus($id, fpPaymentOrderStatusEnum::IN_PROCESS);

    if (empty($order) || !$order->getId()) {
      fpPaymentContext::getInstance()->getPayPal()->getLoger()->add("Transaction '{$id}' not found", 'CANCELLED');
    } else {
      $order->setType(fpPaymentPaypal::NAME);
      $order->setStatus(fpPaymentOrderStatusEnum::CANCELLED);
      $order->save();
      
      $paypal = new fpPaymentPaypal();
      $paypal->setOrderId($order->getId());
      $paypal->setResponse($request->getParameterHolder()->getAll());
      $paypal->save();
    }
  }
  
  /**
   * Callback
   *
   * @param sfWebRequest $request
   *
   * @return void
   */
  public function executeCallback(sfWebRequest $request)
  {
    $params = $request->getParameterHolder()->getAll();
    $payPal = fpPaymentContext::getInstance()->getPayPal();
    $payPal->getLoger()->addArray($params, 'Callback');
    if ($payPal->getIpn()->processCallback($params)) {
      $this->renderText('OK');
    }
    $this->renderText('FAIL');
    return sfView::NONE;
  }
  
  /**
   * Error
   *
   * @param sfWebRequest $request
   *
   * @return void
   */
  public function executeError(sfWebRequest $request)
  {
    fpPaymentContext::getInstance()->getPayPal()->getLoger()->addArray($request->getParameterHolder()->getAll(), 'Error');
  }
  
}