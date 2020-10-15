<?php

class MapSession {

    private $sessionGUID;
    private $success;
    private $errCode;

    public function __construct(array $args)
    {
        $this->sessionGUID = $args['SessionGUID'];
        $this->success = $args['Success'];
        $this->errCode = $args['ErrCode'];
    }

    public function getSessionGUID()
    {
        return $this->sessionGUID;
    }

    public function getErrCode()
    {
        return $this->errCode;
    }

    public function isSuccess()
    {
        return $this->success && empty($this->errCode);
    }
}

class MapPaymentAPI {

    private $key;
    private $pass;
    private $requestMethod;
    private $testmode;
    private $gateway_url;

    public function __construct($gateway_url, $key, $pass, $testmode = false)
    {
        $this->gateway_url = $gateway_url;
        $this->key = $key;
        $this->pass = $pass;
        $this->testmode = $testmode;
    }

    public function setRequestMethod(callable $requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    public function init(array $params)
    {
        $url = $this->getUrl('Init');

        $params['Key'] = $this->key;
        $params['Password'] = $this->pass;
        if (array_key_exists('CustomParams', $params) && $params['CustomParams'] !== null) {
            $params['CustomParams'] = $this->combineParams($params['CustomParams']);
        }

        return $this->sendRequest($url, $params);
    }

    public function getPaymentStateRequest($id, $init_session = false) {
        $url = $this->getUrl( 'getState' );
        $params['Key'] = $this->key;

        if ($init_session) {
            $params['SessionID'] = $id;
        }
        else {
            $params['OrderId'] = $id;
        }

        return $this->sendRequest($url, $params);
    }

    public static function isSuccessState(array $state) {
        if ($state['Success'] && isset($state['State'])) {
            return $state['State'] === 'Charged';
        }

        return false;
    }

    public function getPaymentActionUrl()
    {
        return $this->getUrl('createPayment');
    }

    /**
     * Комбинирует массив параметров в строку
     *
     * @param array $args массив параметров
     * @return string
     */
    private function combineParams(array $args)
    {
        $c_args = array();

        foreach ($args as $key => $value) {
            $c_args[] = $key . '=' . $value;
        }

        return implode(';', $c_args);
    }

    private function getUrl($method)
    {
        return $this->gateway_url . $method;
    }

    private function sendRequest($url, $data)
    {
        if ( $this->requestMethod ) {
            return call_user_func($this->requestMethod, $url, $data);
        }

        return array();
    }

}
