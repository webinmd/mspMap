<?php

if (!class_exists('msPaymentInterface')) {
	require_once dirname(dirname(dirname(__FILE__))) . '/model/minishop2/mspaymenthandler.class.php';
}

class mspMap extends msPaymentHandler implements msPaymentInterface 
{
	public $config;
	public $modx;
	
    private $sessionGUID;
    private $success;
    private $errCode;

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
			,'test' => $this->modx->getOption('ms2_payment_mspmap_pass') 
			,'failId' => $this->modx->getOption('ms2_payment_mspmap_failure_id') 
			,'successId' => $this->modx->getOption('ms2_payment_mspmap_success_id') 
		), $config); 

		$this->api = new MapPaymentAPI(
			$this->config['gateway'],
			$this->config['key'], 
			$this->config['test']
		);

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
			$data = array(
				'merchant_order_id' => $order->get('id'),
				'amount'       		=> number_format($order->get('cost'), 2, '.', '') * 100,
				'type'         		=> 'pay',
				'custom_params' 	=> array(
					'successUrl' 	=> $this->config['paymentUrl'],
					'failUrl' 		=> $this->config['paymentUrl']
					//'successUrl' => $this->config['successId'],
					//'failUrl' => $this->config['failId'],
					//'Email' => $this->config['paymentUrl']
				),
			);

			$request = $this->request( $data, $this->config['gateway'].'/Init' );
			
			$this->sessionGUID = $request['SessionGUID'];
			$this->success = $request['Success'];
			$this->errCode = $request['ErrCode'];

			if ( $this->success && empty($this->errCode) ) { 				
				$Address = $order->getOne('Address');
				$Address->set('metro',$this->sessionGUID );
				$Address->save(); 
				return $this->sessionGUID;
			}		
		}

		$this->paymentError('mspMap - Ошибка создания сессии:', $this->errCode); 
		return null;
 
	}


	public function getPaymentLink($sessionId) 
	{ 
		$link = $this->config['gateway'].'/createPayment'.'?'. http_build_query(['SessionID' => $sessionId]);
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


	public function request($params = array(), $url)
    {

        $request = http_build_query($arams);
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_VERBOSE => 1, 
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $request,
        );

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = curl_error($ch);
        } else {
            $result = array();
            parse_str($response, $result);
        }

        curl_close($ch);

        return $result;
	}

}