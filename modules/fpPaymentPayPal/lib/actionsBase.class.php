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
    $payPal = fpPaymentContext::getInstance()->getPayPal();
    $params = $request->getParameterHolder()->getAll();
    
    $payPal->getLoger()->addArray($params, 'Callback');
    $id = (int)$request->getParameter('invoice');
    $order = fpPaymentOrderTable::getInstance()->findOneByIdAndStatus($id, fpPaymentOrderStatusEnum::IN_PROCESS);
    
    if (empty($order)) {
      $payPal->getLoger()->add('FAIL', 'CALLBACK');
      die('FAIL');
    }
    $order->setType(fpPaymentPaypal::NAME);
    
    unset(
      $params['module'],
      $params['action'],
      $params['orderId']
    );
    
    $paypalModel = new fpPaymentPaypal();
    $paypalModel->setOrderId($order->getId());
    $paypalModel->setCallback($params);
    $paypalModel->save();
    
    $paypalIpn = $payPal->getIpn();    
    $paypalIpn->setData($paypalIpn->processCallbackData($params));
    $paypalIpn->processNotifyValidate();
    
    $order->setStatus($paypalIpn->isVerified()?fpPaymentOrderStatusEnum::SUCCESS:fpPaymentOrderStatusEnum::FAIL);
    $order->save();
    
    $paypalModel->setResponse($paypalIpn->getResponse());
    $paypalModel->save();
    
    die('OK');
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