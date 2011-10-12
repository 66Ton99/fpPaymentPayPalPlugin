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
   * Constructor
   *
   * @return void
   */
  public function __construct()
  {
    $this->getContext()->getDispatcher()->connect('fp_payment_order.after_create', array($this, 'addOrderToValues'));
    $this->getContext()->getDispatcher()->connect('fp_payment.on_process', array($this, 'addItemsToValues'));
    parent::__construct();
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentMethodContext::renderInfoPage()
   */
  public function renderInfoPage(sfAction &$action, sfRequest $request)
  {
    if (fpPaymentContext::getInstance()
          ->getPayPal()
          ->doProcess(array())
          ->getIpn()
          ->hasErrors())
    {
      $action->redirect('@fpPaymentPayPalPlugin_error', 404);
    } else {
      $action->redirect($this->getContext()->getPayPal()->getIpn()->getUrl());
    }
    return sfView::NONE;
  }
  
  /**
   * Event handler. Add items to the values
   *
   * @param sfEvent $event - Keys: context, values
   *
   * @return viod
   */
  public function addItemsToValues(sfEvent $event)
  {
    /* @var $context fpPaymentContext */
    $context = $event['context'];
    $values = $event['values'];
    $i = 1;
    $taxes = 0.0;
    /* @var $val fpPaymentOrderItem */
    foreach ($context->getOrderModel()->getFpPaymentOrderItem() as $val) {
      $values['amount_' . $i] = $val->getPrice();
      $values['item_number_' . $i] = $val->getObjectId();
      $values['item_name_' . $i] = $val->getName();
      $values['quantity_' . $i] = $val->getQuantity();
      if ($tax = $val->getTax()) {
        $taxes += $tax;
      }
      $i++;
    }
    if (!empty($taxes)) {
      $values['tax_cart'] = $taxes;
    }
    $values['currency_code'] = $context->getOrderModel()->getCurrency();
  }
  
  /**
   * Event handler. Add order to values
   *
   * @param sfEvent $event - Keys: values, context
   *
   * @return viod
   */
  public function addOrderToValues(sfEvent $event)
  {
    /* @var $context fpPaymentContext */
    $context = $event['context'];
    $order = $context->getOrderModel();
    $values = $event['values'];
    $values['invoice'] = $order->getId();
    $values['payer_id'] = $context->getCustomer()->getId();
    $order->setType(static::NAME);
    $order->setStatus(fpPaymentOrderStatusEnum::IN_PROCESS);
    $order->save();
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
