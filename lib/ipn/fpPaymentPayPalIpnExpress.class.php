<?php

/**
 * Express paypal IPN
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnExpress extends fpPaymentPayPalIpnBase
{
  
  protected $urlsKeys = array('errorURL', 'returnURL', 'cancelURL', 'callback');
  
  protected $options = array(
    'checkout_url' => 'www.paypal.com',
    'checkout_url_path' => '/webscr&cmd=_express-checkout&token=',
    'url' => 'api-3t.paypal.com',
    'url_path' => '/',
    'protocol' => 'nvp',
    'fields' => array(
      'METHOD' => '', // SetExpressCheckout, 
      'VERSION' => '56.0',
      'USER' => '',
      'PWD' => '',
      'SIGNATURE' => '',
      'errorURL' => '@fpPaymentPayPalPlugin_error',
      'returnURL' => '@fpPaymentPayPalPlugin_success',
      'cancelURL' => '@fpPaymentPayPalPlugin_cancelled',
      'callback' => '@fpPaymentPayPalPlugin_callback',
      'callbackVersion' => '56.0',
      'Amt' => '',
      'paymentType' => '', // Authorization or 'Sale' or 'Order'
      'CURRENCYCODE' => 'USD',
    )
  );

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
    $configOptions = sfConfig::get('fp_payment_paypal_ipn', array('express' => $this->options));
    
    $configOptions = $configOptions['express'];
    $functionsClassName = sfConfig::get('fp_payment_functions_class_name',  'fpPaymentFunctions');
    $this->options = $functionsClassName::arrayMergeRecursive($configOptions, $options);
    $data = $this->options['fields'];
    
    parent::setData($this->convertRoutesToUrls($data));
    $this->getContext()
      ->getDispatcher()
        ->connect('fp_payment_order.after_create', array($this, 'addOrderToValues'));
    $this->getContext()
      ->getDispatcher()
        ->connect('fp_payment.on_process', array($this, 'addItemsToValues'));
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
    if ($token = $this->getToken()) {
      $this->redirectUrl = 'https://' . $this->options['checkout_url'] . $this->options['checkout_url'] . urlencode($token);
      $this->getLoger()
        ->add('Redirecting to ' . $this->getRedirectUrl());
    } else {
      $this->redirectUrl = $this->options['fields']['errorURL'];
      $this->getLoger()
        ->addArray($this->getResponse(), 'Erorr', 'Process');
    }
    return $this;
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentIpnBase::getUrl()
   */
  public function getUrl()
  {
    return 'https://' . $this->options['url'] . $this->options['url_path'] . '/' . $this->options['protocol'];
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
    $this->getConnection($this->getUrl());
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
  
  /**addOrderToValues
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
   * @see fpPaymentPayPalIpnBase::processCallbackData()
   */
  public function processCallbackData($data)
  {
//    $data['receiver_email'] = $this->options['form_hidden_fields']['business'];
    return $data;
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentPayPalIpnBase::getToken()
   */
  public function getToken()
  {
    $connection = $this->getConnection($this->getUrl());
    $data = $this->getData();
    foreach ($this->getUrlKeys() as $key) {
      $data[$key] = $data[$key] . urlencode('?orderId=' . $orderId);
    }
    $this->getLoger()
      ->addArray($data, 'Get token by ' . $this->getUrl());
    $this->response = $this->getProtocol()->toArray($connection->sendPostRequest($this->getProtocol()->fromArray($data)));
    if ('SUCCESS' == strtoupper(substr($this->response['ACK'], 0, 7))) {
      return empty($this->response['TOKEN'])?false:$this->response['TOKEN'];
    }
    return false;
  }
}