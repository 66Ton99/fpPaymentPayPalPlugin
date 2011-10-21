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
      $params['action']
    );
    
    $paypalModel = new fpPaymentPaypal();
    $paypalModel->setOrderId($order->getId());
    $paypalModel->setResponse($params);
    $paypalModel->save();
    
    $paypalFormHiddenFields = sfConfig::get('fp_payment_paypal_form_hidden_fields', array('business' => ''));
    $params['receiver_email'] = $paypalFormHiddenFields['business'];
    
    $paypalIpn = $payPal->getIpn();
    $paypalIpn->setData($params);
    $paypalIpn->processNotifyValidate();
    if ($paypalIpn->isVerified()) {
      $order->setStatus(fpPaymentOrderStatusEnum::SUCCESS);
      $order->save();
    } else {
      $order->setStatus(fpPaymentOrderStatusEnum::FAIL);
      $order->save();
    }
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