<?php

if (!class_exists('msPaymentInterface')) {
	require_once dirname(dirname(dirname(__FILE__))) . '/model/minishop2/mspaymenthandler.class.php';
}


/////////////////////////////////////
require_once("vendor/autoload.php"); 
use GuzzleHttp\Client;
/////////////////////////////////////

class mspMap extends msPaymentHandler implements msPaymentInterface 
{
	public $config;
	public $modx;
	
    private $sessionGUID;
    private $success;
    private $errCode;
    private $client;

	function __construct(xPDOObject $object, $config = array()) 
	{
		$this->modx = & $object->xpdo;

		$siteUrl = $this->modx->getOption('site_url');
		$assetsUrl = $this->modx->getOption('minishop2.assets_url', $config, $this->modx->getOption('assets_url').'components/minishop2/');
		$paymentUrl = $siteUrl . substr($assetsUrl, 1) . 'payment/mspmap.php';

		$this->config = array_merge(array(
			 'paymentUrl' => $paymentUrl
			,'gateway' => $this->modx->getOption('ms2_payment_mspmap_gateway_url')
			,'key' => $this->modx->getOption('ms2_payment_mspmap_key') 
			,'pass' => $this->modx->getOption('ms2_payment_mspmap_pass') 
			,'test' => $this->modx->getOption('ms2_payment_mspmap_test_mode') 
			,'failId' => $this->modx->getOption('ms2_payment_mspmap_failure_id') 
			,'successId' => $this->modx->getOption('ms2_payment_mspmap_success_id') 
		), $config); 

		$options = [
			'base_url' 	=> $this->config['gateway'],
			'debug'  	=> false,
			'verify' 	=> false
		]; 

		$this->client = new Client($options);

	}


	/* @inheritdoc} */
	public function send(msOrder $order) 
	{
		
		if($sessionId = $this->getSessionId($order)) {
			$link = $this->getPaymentLink($sessionId);
			return $this->success('', array('redirect' => $link));
		} else {		
			$this->paymentError('mspMap - Ошибка получения сессии:'); 
			return null;
		}

	}



	public function getSessionId($order) 
	{

		if($sessionId = $this->get_session_id($order)) {	

			return $sessionId;

		} else {	
 
			$data = [
				'Key' 			=>  $this->config['key'],
				'Password' 		=>  $this->config['pass'],
				'OrderId' 		=> $order->get('id'),
				'Amount'       	=> number_format($order->get('cost'), 2, '.', '') * 100,
				'Type'         	=> 'Pay',
				'CustomParams' 	=> "successUrl=".$this->config['paymentUrl'].";successUrl=".$this->config['paymentUrl']."Email=89poilo@gmail.com"
			];

			$request = $this->client->request('POST',  $this->config['gateway'].'Init', [	
				'headers' => [ 
					'Content-Type' => 'application/x-www-form-urlencoded'
				],
				'form_params' => $data
			]);

			$response = $request->getBody()->getContents();
			$responseArray = json_decode($response, true);

			if($responseArray['Success']) {
				$sessionGUID = $responseArray['SessionGUID']; 			
				$Address = $order->getOne('Address');
				$Address->set('metro',$sessionGUID );
				$Address->save(); 
				return $sessionGUID;			 
			} 
				
		}

		$this->paymentError('mspMap - Ошибка создания сессии:'); 
		return null;
 
	}


	public function getPaymentLink($sessionId) 
	{ 
		$link = $this->config['gateway'].'createPayment'.'?'. http_build_query(['SessionID' => $sessionId]);
		return $link;
	}


	/* @inheritdoc} */
	public function receive(msOrder $order, $params = array()) 
	{

		$id = $order->get('id'); 

		if ($params['result'] == 'success') {
			/* @var miniShop2 $miniShop2 */
			$miniShop2 = $this->modx->getService('miniShop2');
			@$this->modx->context->key = 'mgr';
			$miniShop2->changeOrderStatus($order->get('id'), 2);
			exit('OK');
		}
		else {
			$this->paymentError('Err: wrong response.', $params);
		}
	}


	public function paymentError($text, $request = array()) 
	{
		$this->modx->log(modX::LOG_LEVEL_ERROR,'[miniShop2:mspMap] ' . $text . ', request: '.print_r($request,1));
		header("HTTP/1.0 400 Bad Request");

		die('ERR: ' . $text);
	}


	private function get_session_id( msOrder $order ) 
	{ 		
		$Address = $order->getOne('Address');		
		return $Address->get('metro');
	}


}