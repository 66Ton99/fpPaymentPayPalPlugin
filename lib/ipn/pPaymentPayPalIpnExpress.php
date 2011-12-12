<?php

/**
 * Class that works with PayPal Instant fpPayment Notification. It takes what was
 * sent from PayPal and sends an indentical response back to PayPal, then waits
 * for verification from PayPal
 *
 * @package    fpPayment
 * @subpackage PayPal
 * @author		 Ton Sharp <Forma-PRO@66ton99.org.ua>
 */
class fpPaymentPayPalIpnExpress extends fpPaymentIpnBase
{

  protected $curl;
  
  protected $options = array(
    'url' => 'www.paypal.com',
    'url_path' => '/cgi-bin/webscr?',
    'formFields' => array(
      'amount_1' => 0,
      'item_name_1' => '',
      'quantity_1' => 0,
    ),
    'formHiddenFields' => array(
      '_info' => 'Array',
      'cmd' => '_cart',
      'upload' => '1',
      'shipping' => '0.00',
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
  public function __construct($options)
  {
    $this->options;
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
    $this->url = 'https://' . sfConfig::get('fp_payment_paypal_form_url', 'www.paypal.com') . 
                    sfConfig::get('fp_payment_paypal_form_url_path', '/cgi-bin/webscr');
    $data = sfConfig::get('fp_payment_paypal_form_fields', $this->formFields);
    $data = array_merge($data, sfConfig::get('fp_payment_paypal_form_hidden_fields', $this->formHiddenFields));
    foreach ($this->getUrlKeys() as $key) {
      $data[$key] = urlencode(url_for($data[$key], true));
    }
    
    fpPaymentContext::getInstance()
      ->getDispatcher()
        ->connect('fp_payment_order.after_create', array($this, 'addOrderToValues'));
    fpPaymentContext::getInstance()
      ->getDispatcher()
        ->connect('fp_payment.on_process', array($this, 'addItemsToValues'));
    
    parent::setData($data);
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
    $this->url .= $this->curl->prepareRequest($this->data);
    fpPaymentContext::getInstance()
      ->getPayPal()
      ->getLoger()
      ->addArray($this->data, 'Send data to ' . $this->url);
    return $this;
  }
  
  /**
   * Get url
   *
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
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
    fpPaymentContext::getInstance()
      ->getPayPal()
      ->getLoger()
      ->addArray($data, 'Send notify data to ' . $this->getUrl() . $this->curl->prepareRequest($this->getData()));

    $this->response = $this->curl->sendPostRequest($data);
    fpPaymentContext::getInstance()
      ->getPayPal()
      ->getLoger()
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
    $order->setType(static::NAME);
    $order->setStatus(fpPaymentOrderStatusEnum::IN_PROCESS);
    $order->save();
  }
}