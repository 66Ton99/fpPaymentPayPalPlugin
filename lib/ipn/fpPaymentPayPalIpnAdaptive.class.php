<?php

/**
 * Adaptive paypal IPN
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnAdaptive extends fpPaymentPayPalIpnBase
{
  
  protected $urlsKeys = array('errorUrl', 'returnUrl', 'cancelUrl', 'ipnNotificationUrl');
  
  protected $options = array(
    'url' => 'svcs.paypal.com',
    'url_path' => '/AdaptivePayments/',
    'checkout_url' => 'www.paypal.com',
    'checkout_url_path' => '/webscr?cmd=_ap-payment&paykey=',
    'headers' => array(
      'X-PAYPAL-SECURITY-USERID' => '',
      'X-PAYPAL-SECURITY-SIGNATURE' => '',
      'X-PAYPAL-SECURITY-PASSWORD' => '',
      'X-PAYPAL-APPLICATION-ID' => '',
      'X-PAYPAL-DEVICE-IPADDRESS' => '',
     	'X-PAYPAL-REQUEST-DATA-FORMAT' => 'NV',
     	'X-PAYPAL-RESPONSE-DATA-FORMAT' => 'NV',
      'X-PAYPAL-SERVICE-VERSION' => '1.7.0',
    ),
    'fields' => array(
      'errorUrl' => '@fpPaymentPayPalPlugin_error',
      'returnUrl' => '@fpPaymentPayPalPlugin_success',
      'cancelUrl' => '@fpPaymentPayPalPlugin_cancelled',
      'ipnNotificationUrl' => '@fpPaymentPayPalPlugin_callback',
      'requestEnvelope.errorLanguage' => 'en_US',
      'currencyCode' => 'USD',
      'actionType' => '',
      'receiverList.receiver(0).email' => '',
      'receiverList.receiver(0).amount' => ''
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
    $configOptions = sfConfig::get('fp_payment_paypal_ipn', array('adaptive' => $this->options));
    
    $configOptions = $configOptions['adaptive'];
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
   * @see fpPaymentPayPalIpnBase::processCallbackData()
   */
  public function processCallbackData($data)
  {
//    $data['receiver_email'] = $this->options['form_hidden_fields']['business'];
    return $data;
  }
  
	/**
	 * Get token
	 *
	 * @return string|false
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
    $connection->setHeader($this->options['headers']);
    $this->response = $this->getProtocol()->toArray($connection->sendPostRequest($this->getProtocol()->fromArray($data)));
    if ('SUCCESS' == strtoupper($this->response['responseEnvelope_ack'])) {
      return empty($this->response['payKey'])?false:$this->response['payKey'];
    }
    return false;
  }
}