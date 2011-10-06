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
class fpPaymentPayPalIpn extends fpPaymentIpnBase
{

  protected $curl;
  
  protected $formFields = array(
    'amount_1' => 0,
    'item_name_1' => '',
    'quantity_1' => 0,
  );
  
  protected $urlsKeys = array('cancel_return', 'notify_url', 'return');
  
  protected $formHiddenFields = array(
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
  );

  /**
   * Constructor
   *
   * @param unknown_type $paypalPostVars
   * @param int $timeout
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
    $this->url = 'https://' . sfConfig::get('fp_payment_paypal_form_url', 'www.paypal.com') . 
                    sfConfig::get('fp_payment_paypal_form_url_path', '/cgi-bin/webscr');
    $data = sfConfig::get('fp_payment_paypal_form_fields', $this->formFields);
    $data = array_merge($data, sfConfig::get('fp_payment_paypal_form_hidden_fields', $this->formHiddenFields));
    foreach ($this->getUrlKeys() as $key) {
      $data[$key] = urlencode(url_for($data[$key], true));
    }
    
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
    $this->curl = new fpPaymentCurl($this->url);
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
    $this->curl = new fpPaymentCurl($this->getUrl());
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
}