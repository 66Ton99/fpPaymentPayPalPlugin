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
    'url_path' => '/AdaptivePayments/Pay',
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
//      'feesPayer' => '', // SENDER, PRIMARYRECEIVER, EACHRECEIVER, SECONDARYONLY
      'actionType' => 'PAY',
      'receiverList.receiver(0).email' => '',
      'receiverList.receiver(0).amount' => '',
//      'receiverList.receiver(0).primary' => 'true', // true, false
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
    $this->options = $functionsClassName::arrayMergeRecursive($this->options, $configOptions, $options);
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
      $this->redirectUrl = 'https://' . $this->options['checkout_url'] . $this->options['checkout_url_path'] . urlencode($token);
    } else {
      $this->redirectUrl = $this->options['fields']['errorUrl'];
    }
    $this->getLoger()
        ->add('Redirecting to ' . $this->getRedirectUrl());
    return $this;
  }
  
  /**
   * (non-PHPdoc)
   * @see fpPaymentIpnBase::getUrl()
   */
  public function getUrl()
  {
    return 'https://' . $this->options['url'] . $this->options['url_path'];
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
    $values['receiverList.receiver(0).amount'] = $context->getOrderModel()->getSum();
    $values['currencyCode'] = $context->getOrderModel()->getCurrency();
    $values['customerId'] = $context->getOrderModel()->getCustomerId();
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
    $values['invoiceId'] = $order->getId();
    $order->setType(fpPaymentPayPalContext::NAME);
    $order->setStatus(fpPaymentOrderStatusEnum::IN_PROCESS);
    $order->save();
  }
  
  /**
   * (non-PHPdoc)
   * @todo finish
   * @see fpPaymentPayPalIpnBase::processCallback()
   */
  public function processCallback($data)
  {
    // TODO Implement
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
      $data[$key] = $data[$key] . '?orderId=' . $this->getOrderId();
    }
    $this->getLoger()
      ->addArray($data, 'Get token by ' . $this->getUrl());
    $headers = $this->options['headers'];
    $headers['X-PAYPAL-DEVICE-IPADDRESS'] = $_SERVER['REMOTE_ADDR'];
    $connection->setHeader($headers);
    $this->response = $this->getProtocol()->toArray($connection->sendPostRequest($this->getProtocol()->fromArray($data)));
    $this->getLoger()
      ->addArray($this->response, 'Get token response');
    if ('SUCCESS' == strtoupper($this->response['responseEnvelope_ack'])) {
      return empty($this->response['payKey'])?false:$this->response['payKey'];
    }
    return false;
  }
}