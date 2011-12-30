<?php

/**
 * Class that works with PayPal Instant fpPayment Notification. It takes what was
 * sent from PayPal and sends an indentical response back to PayPal, then waits
 * for verification from PayPal
 * 
 * NOTE it is wrong one don't use it!!!
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnStandard extends fpPaymentPayPalIpnBase
{

  protected $curl;
  
  protected $options = array(
    'url' => 'www.paypal.com',
    'url_path' => '/cgi-bin/webscr?',
    'form_fields' => array(
      'amount_1' => 0,
      'item_name_1' => '',
      'quantity_1' => 0,
    ),
    'form_hidden_fields' => array(
      '_info' => 'Array',
      'cmd' => '_cart',
      'upload' => 1,
      'shipping' => 0.00,
      'cancel_return' => '@fpPaymentPayPalPlugin_cancelled',
      'notify_url' => '@fpPaymentPayPalPlugin_callback',
      'return' => '@fpPaymentPayPalPlugin_success',
      'redirect_cmd' => '_xclick',
      # other:
      'currency_code' => '',
//    'custom' => '',
      'invoice' => 0,
      # required:
      'business' => ''
    )
    
  );
  
  protected $urlsKeys = array('cancel_return', 'notify_url', 'return');

  /**
   * Constructor
   *
   * @param unknown_type $paypalPostVars
   * @param int $timeout
   *
   * @return void
   */
  public function __construct($options = array())
  {
    $configOptions = sfConfig::get('fp_payment_paypal_ipn', array('standard' => $this->options));
    
    $configOptions = $configOptions['standard'];
    $this->options = array_merge($configOptions, $options);
    $this->url = 'https://' . $this->options['url'] . $this->options['url_path'];
    
    $data = array_merge($this->options['form_fields'], $this->options['form_hidden_fields']);
    
    parent::setData($this->convertRoutesToUrls($data));
    $this->getContext()
      ->getDispatcher()
        ->connect('fp_payment_order.after_create', array($this, 'addOrderToValues'));
    $this->getContext()
      ->getDispatcher()
        ->connect('fp_payment.on_process', array($this, 'addItemsToValues'));
  }
  
  /**
   * Retrun keys of urls
   *
   * @return array
   */
  public function getUrlKeys()
  {
    return $this->urlsKeys;
  }

  /**
   * returns true if paypal says the order is good, false if not
   *
   * @return bool
   */
  public function isVerified()
  {
    return (0 == strcmp('VERIFIED', $this->response));
  }

  /**
   * returns the paypal payment status
   * 
   * @TODO complet
   *
   * @return string
   */
  public function getPaymentStatus()
  {
    return $this->paypalPostVars['payment_status'];
  }

  /**
   * (non-PHPdoc)
   * @see fpPaymentIpnBase::process()
   */
  public function process()
  {
    $this->curl = new fpPaymentConnection($this->url);
    $orderId = fpPaymentContext::getInstance()->getOrderModel()->getId();
    foreach ($this->getUrlKeys() as $key) {
      $this->data[$key] = $this->data[$key] . urlencode('?orderId=' . $orderId);
    }
    $this->redirectUrl =  $this->url . $this->curl->prepareRequest($this->data);
    $this->getLoger()
      ->addArray($this->data, 'Send data to ' . $this->redirectUrl);
    return $this;
  }
  
  /**
   * Checks come data
   *
   * @param array $params
   *
   * @return fpPaymentPaypalIpn
   */
  public function processNotifyValidate()
  {
    $this->curl = new fpPaymentConnection($this->getUrl());
    $data = $this->getData();
    if (!empty($data['cmd'])) {
      $data['cmd'] = '_notify-validate';
    } else {
      $data = array_merge(array('cmd' => '_notify-validate'), $data);
    }
    $this->getLoger()
      ->addArray($data, 'Send notify data to ' . $this->getUrl() . $this->curl->prepareRequest($this->getData()));

    $this->response = $this->curl->sendPostRequest($data);
    $this->getLoger()
      ->add($this->response, 'Get notify data');
    return $this;
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
      $values['shipping_' . $i] = $val->getShipping();
      if ($tax = $val->getTax()) {
        $taxes += $tax;
      }
      $i++;
    }
    if (!empty($taxes)) {
      $values['tax_cart'] = round($taxes, 2);
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
    $order->setType(fpPaymentPayPalContext::NAME);
    $order->setStatus(fpPaymentOrderStatusEnum::IN_PROCESS);
    $order->save();
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentPayPalIpnBase::processCallback()
   */
  public function processCallback($data)
  {
    if (empty($data['orderId'])) return false;
    $id = (int)$data['orderId'];
    $order = fpPaymentOrderTable::getInstance()->findOneByIdAndStatus($id, fpPaymentOrderStatusEnum::IN_PROCESS);
    
    if (empty($order)) {
      $this->getLoger()->add("FAIL order with id: '{$id}' don't found", 'CALLBACK');
      return false;
    }
    if (fpPaymentPaypal::NAME != $order->getType()) {
      $this->getLoger()->add('FRAUD', 'CALLBACK');
      return false;
    }
    
    unset(
      $data['module'],
      $data['action'],
      $data['orderId']
    );
//     $data['receiver_email'] = $this->options['form_hidden_fields']['business'];
    
    $paypalModel = new fpPaymentPaypal();
    $paypalModel->setOrderId($order->getId());
    $paypalModel->setCallback($data);
    $paypalModel->save();
    
    $this->setData($data);
    $this->processNotifyValidate();
    
//     $order->setStatus($this->isVerified()?fpPaymentOrderStatusEnum::SUCCESS:fpPaymentOrderStatusEnum::FAIL);
//     $order->save();
    
    $paypalModel->setResponse($this->getResponse());
    $paypalModel->save();
    return true;
  }
  
  /**
   * Do nothing
   * 
   * @see fpPaymentPayPalIpnBase::getToken()
   */
  public function getToken() {}
}