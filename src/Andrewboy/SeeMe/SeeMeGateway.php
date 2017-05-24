<?php namespace Andrewboy\SeeMe;

use Andrewboy\SeeMe\Exceptions\SeeMeGatewayException;
use \Exception as Exception;

/**
 *
 * SeeMe SMS Gateway class
 *
 * @version 2.0.1 SeeMeGateway
 *
 * @link https://seeme.hu
 *
 */

/**
 * CHANGELOG
 *  1.0.2 sendSMS() method: removed 0 param @ line: 107
 *  1.0.1 http_build_query() added param arg_separator: '&amp;' @ line: 225
 *  2.0.0 uses API key, new parameter validators
 *  2.0.1 reference validator hotfix
 */

class SeeMeGateway
{
    /**
     * Result statuses
     */
    const RESULT_STATUS_OK = 'ok';
    const RESULT_STATUS_ERR = 'err';

    /**
     * Format types
     */
    const FORMAT_XML = 'xml';
    const FORMAT_STRING = 'string';
    const FORMAT_JSON = 'json';

    /**
     * Method types
     */
    const METHOD_CURL = 'curl';
    const METHOD_FILE_GET_CONTENTS = 'file_get_contents';

    /**
     * API data
     */
    const API_URL = 'https://seeme.hu/gateway';
    const API_VERSION = '2.0.1';

    protected $method;
    protected $logFileDestination = false;
    protected $format;
    protected $log = '';
    protected $params = [];
    protected $result;

    /**
     * SeeMeGateway constructor.
     * @param string $apiKey
     * @param string|bool $logFileDestination
     * @param string $format
     * @param string $method
     */
    public function __construct($apiKey, $logFileDestination = false, $format = self::FORMAT_JSON, $method = self::METHOD_CURL)
    {
        $this->setApiKey($apiKey);
        $this->setFormat($format);
        $this->setMethod($method);
        $this->setLogFileDestination($logFileDestination);
    }

    /**
     * Set API key
     * @param string $apiKey
     * @throws SeeMeGatewayException
     */
    public function setApiKey($apiKey)
    {
        if (!is_string($apiKey)) {
            throw new SeeMeGatewayException('Invalid API key type. Must be string', 1);
        }

        if (!$this->validateApiKey($apiKey)) {
            throw new SeeMeGatewayException('Invalid API key', 18);
        }

        $this->params['key'] = trim($apiKey);
    }

    /**
     * Set format
     * @param string $format
     * @throws SeeMeGatewayException
     */
    public function setFormat($format)
    {
        $validFormatTypes = [static::FORMAT_STRING, static::FORMAT_JSON, static::FORMAT_XML];

        if (!is_string($format) || !in_array($format, $validFormatTypes)) {
            throw new SeeMeGatewayException('Invalid format. Must be string or format have to be in ['. implode(', ', $validFormatTypes) .']', 1);
        }

        $this->format = $format;
    }

    /**
     * Set method
     * @param string $method
     * @throws SeeMeGatewayException
     */
    public function setMethod($method)
    {
        $validMethodTypes = [static::METHOD_CURL, static::METHOD_FILE_GET_CONTENTS];

        if (!is_string($method) || !in_array($method, $validMethodTypes)) {
            throw new SeeMeGatewayException('Invalid method. Must be string or method have to be in ['. implode(', ', $validMethodTypes) .']', 1);
        }

        $this->method = $method;
    }

    /**
     * Set log file destination
     * @param bool|string $logFileDestination
     * @throws SeeMeGatewayException
     */
    public function setLogFileDestination($logFileDestination)
    {
        if (false !== $logFileDestination && !is_string($logFileDestination)) {
            throw new SeeMeGatewayException('Invalid log file destination. Must be string or boolean false', 1);
        }

        $this->logFileDestination = $logFileDestination;
    }

    /**
     * Set tel number
     * @param array $params
     * @param string $number Numeric
     * @throws SeeMeGatewayException
     */
    protected function setTelNumber(array &$params, $number)
    {
        if (!is_string($number)) {
            throw new SeeMeGatewayException('Invalid number  parameter type. Must be string', 1);
        }

        if (!is_numeric($number)) {
            throw new SeeMeGatewayException("Only numbers are allowed: number", "2");
        }

        $params['number'] = trim($number);
    }

    /**
     * Set message
     * @param array $params
     * @param string $message
     * @throws SeeMeGatewayException
     */
    protected function setMessage(array &$params, $message)
    {
        if (!is_string($message) || strlen($message) < 1) {
            throw new SeeMeGatewayException('Invalid message parameter type. Must be a not empty string', 1);
        }

        $params['message'] = trim($message);
    }

    /**
     * Set sender
     * @param array $params
     * @param string|null $sender
     * @throws SeeMeGatewayException
     */
    protected function setSender(array &$params, $sender)
    {
        if (!is_string($sender) && !is_null($sender)) {
            throw new SeeMeGatewayException('Invalid sender parameter type. Must be string', 1);
        }

        if (is_string($sender)) {
            $params['sender'] = trim($sender);
        }
    }

    /**
     * Set reference
     * @param array $params
     * @param string|null $reference Numeric type
     * @throws SeeMeGatewayException
     */
    protected function setReference(array &$params, $reference)
    {
        if (!is_null($reference) && !is_string($reference) && !is_numeric($reference)) {
            throw new SeeMeGatewayException('Invalid number reference type. Must be string, number or null', 1);
        }

        if (is_string($reference) && is_numeric($reference)) {
            $params['reference'] = trim($reference);
        }
    }

    /**
     * Set callback params
     * @param array $params
     * @param string|null $callbackParams
     * @throws SeeMeGatewayException
     */
    protected function setCallbackParams(array &$params, $callbackParams)
    {
        if (!is_null($callbackParams) && !is_string($callbackParams)) {
            throw new SeeMeGatewayException('Incorrect callback parameter format. Must be string or null', 1);
        }

        if (!is_null($callbackParams)) {
            if ($callbackParams == "all") {
                $params['callback'] = "1,2,3,4,5,6,7,8,9,10";
            } else {
                if ($this->validateCallbackParams($callbackParams)) {
                    $params['callback'] = $callbackParams;
                } else {
                    throw new SeeMeGatewayException('Incorrect callback parameter format', 1);
                }
            }
        }
    }

    /**
     * Set callback URL
     * @param array $params
     * @param string|null $callbackUrl
     * @throws SeeMeGatewayException
     */
    protected function setCallbackUrl(array &$params, $callbackUrl)
    {
        if (!is_null($callbackUrl) && !is_string($callbackUrl)) {
            throw new SeeMeGatewayException('Incorrect callback URL format. Must be string or null', 1);
        }

        if (is_string($callbackUrl)) {
            $params['callbackurl'] = $callbackUrl;
        }
    }

    /**
     * Set IP parameter
     * @param array $params
     * @param string $ip
     * @throws SeeMeGatewayException
     */
    protected function setIpParam(array $params, $ip)
    {
        if (!is_string($ip)) {
            throw new SeeMeGatewayException('Incorrect ip parameter format. Must be string', 1);
        }

        if (!$this->validateIP($ip)) {
            throw new SeeMeGatewayException("Parameter is invalid: ip", 15);
        }

        $params['ip'] = trim($ip);
    }

    /**
     * Send SMS. Throws an exception on error
     *
     * @access public
     * @param string $number Mobile phone number in international format (pl. 36201234567)
     * @param string $message Message encoded in UTF-8
     * @param string|null $sender Sender ID
     * @param string|null $reference
     * @param string|null $callbackParams
     * @param string|null $callbackURL
     * @return array
     * @throws Exception
     */
    public function sendSMS(
        $number,
        $message,
        $sender = null,
        $reference = null,
        $callbackParams = null,
        $callbackURL = null
    ) {
        $params = $this->params;

        $this->setLog('');
        $this->addLog('--------------------------------------------------------------------');
        $this->addLog('SEE ME - SEND SMS');
        $this->addLog('INPUT PARAMS');
        $this->addLog('number: ' . serialize($number));
        $this->addLog('message: ' . serialize($message));
        $this->addLog('sender: ' . serialize($sender));
        $this->addLog('reference: ' . serialize($reference));
        $this->addLog('callback_params: ' . serialize($callbackParams));
        $this->addLog('callback_url: ' . serialize($callbackURL));
        $this->addLog('default_params: ' . serialize($params));

        try {
            $this->setTelNumber($params, $number);
            $this->setMessage($params, $message);
            $this->setSender($params, $sender);
            $this->setReference($params, $reference);
            $this->setCallbackParams($params, $callbackParams);
            $this->setCallbackUrl($params, $callbackURL);
        } catch (Exception $e) {
            $this->addLog('Exception thrown ('. get_class($e) .'): ' . $e->getMessage());
            $this->logToFile($this->getLog());
            throw $e;
        }

        try {
            $result = $this->fetchResult($params);
        } catch (\Exception $e) {
            $this->addLog('Exception thrown ('. get_class($e) .'): ' . $e->getMessage());
            throw $e;
        } finally{
            $this->logToFile($this->getLog());
        }

        return $result;
    }

    /**
     * Fetch the result through API call
     *
     * @param array $params
     * @return array
     */
    protected function fetchResult(array $params)
    {
        $rawResult = $this->callAPI($params);
        $this->addLog('raw_result: ' . serialize($rawResult));

        return $this->parseResult($rawResult);
    }

    /**
     * Get balance
     * @return array
     * @throws Exception
     */
    public function getBalance()
    {
        $this->setLog('');
        $this->addLog('--------------------------------------------------------------------');
        $this->addLog('SEE ME - GET BALANCE');

        $params = $this->params;
        $params['method'] = 'balance';

        $this->addLog('params: ' . serialize($params));

        try {
            $result = $this->fetchResult($params);
        } catch (\Exception $e) {
            $this->addLog('Exception thrown ('. get_class($e) .'): ' . $e->getMessage());
            throw $e;
        } finally{
            $this->logToFile($this->getLog());
        }

        return $result;
    }

    /**
     * Set IP
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function setIP($ip)
    {
        $this->setLog('');
        $this->addLog('--------------------------------------------------------------------');
        $this->addLog('SEE ME - SET IP');
        $this->addLog('INPUT PARAMS');
        $this->addLog('number: ' . serialize($ip));

        $params = $this->params;
        $params['method'] = 'setip';

        try {
            $this->setIpParam($params, $ip);
        } catch (Exception $e) {
            $this->addLog('Exception thrown ('. get_class($e) .'): ' . $e->getMessage());
            $this->logToFile($this->getLog());
            throw $e;
        }

        $this->addLog('params: ' . serialize($params));

        try {
            $result = $this->fetchResult($params);
        } catch (\Exception $e) {
            $this->addLog('Exception thrown ('. get_class($e) .'): ' . $e->getMessage());
            throw $e;
        } finally{
            $this->logToFile($this->getLog());
        }

        return $result;
    }

    /**
     * Returns the call's result
     *
     * @access public
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Parse the callback result
     *
     * @access protected
     * @param string $result
     * @return array
     * @throws Exception
     */
    protected function parseResult($result)
    {
        switch ($this->format) {
            case self::FORMAT_STRING:
                if (!is_string($result)) {
                    throw new Exception("SeeMe Gateway: Wrong return format type. Must be a string");
                }

                parse_str($result, $resultParts);
                break;

            case self::FORMAT_JSON:
                $resultParts = json_decode($result, true);
                break;

            case self::FORMAT_XML:
                $resultParts = json_decode(json_encode((array)simplexml_load_string($result)), 1);
                break;

            default:
                throw new Exception("SeeMe Gateway: Unexpected return format");
                break;
        }

        $this->result = $resultParts;
        $this->addLog('parsed_result: ' . serialize($this->result));

        if (is_array($resultParts) && array_key_exists('result', $resultParts)) {
            switch (strtolower($resultParts['result'])) {
                case self::RESULT_STATUS_OK:
                    // SMS submitted successfully
                    return $resultParts;
                    break;

                case self::RESULT_STATUS_ERR:
                    // error during SMS submit
                    throw new SeeMeGatewayException(
                        $resultParts['message'],
                        $resultParts['code']
                    );
                    break;

                default:
                    throw new Exception('SeeMe Gateway: unimplemented result ' . 'raw result: "' . $result . '"');
                    break;
            }
        } else {
            throw new Exception('Bad result format! Raw result: '. $result);
        }
    }

    /**
     * Call API
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    protected function callAPI(array $params)
    {
        if (isset($this->format)) {
            $params['format'] = $this->format;
        }

        $params['apiVersion'] = self::API_VERSION; // SeeMe GW api version
        $apiUrl = self::API_URL . '?' . http_build_query($params, '', '&');

        $this->addLog('api_url: ' . $apiUrl);

        switch (trim($this->method)) {
            case self::METHOD_FILE_GET_CONTENTS:
                if (!ini_get('allow_url_fopen')) {
                    throw new Exception("SeeMe Gateway: can't use allow_url_fopen method.");
                }
                $result = file_get_contents($apiUrl);
                break;

            case self::METHOD_CURL:
                if (!extension_loaded('curl')) {
                    throw new Exception('SeeMe Gateway: CURL not installed on your server');
                }
                $result = $this->callCURL($apiUrl);
                break;

            default:
                throw new Exception('SeeMe Gateway: unimplemented callingMethod: "' . $this->method . '"');
        }

        if ($result === false) {
            throw new Exception('SeeMe Gateway: failed to open file_get_contents("' . $apiUrl . '")');
        }

        return $result;
    }

    /**
     * Call CURL
     * @param $apiUrl
     * @return mixed
     * @throws Exception
     */
    protected function callCURL($apiUrl)
    {
        $cURL = curl_init();

        curl_setopt_array($cURL, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => true,
            // CURLOPT_CONNECTTIMEOUT_MS => 2000,
            // CURLOPT_TIMEOUT           => 10,
            CURLOPT_FAILONERROR => true,
        ));

        $result = curl_exec($cURL);
        $httpcode = curl_getinfo($cURL, CURLINFO_HTTP_CODE);

        if ($result === false) {

            throw new Exception(
                'SeeMe Gateway: CURL ERROR: ' . $httpcode . ', ' . curl_error($cURL)
            );
        } else {
            return $result;
        }
    }

    /**
     * Validate callback parameters
     *
     * @access protected
     * @param string $params
     * @return boolean
     */
    protected function validateCallbackParams($params)
    {
        return preg_match('/^[0-9]{1,2}(\,[0-9]{1,2})*$/', $params);
    }

    /**
     * Validate IP parameter
     *
     * @access protected
     * @param string $ip
     * @return boolean
     */
    protected function validateIP($ip)
    {
        return preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/",
            $ip);
    }

    /**
     * Validate API key
     * @param $hash
     * @return bool
     */
    protected function validateApiKey($hash)
    {
        $checksumLength = 4;
        $key = substr($hash, 0, -$checksumLength);
        $checksum = substr($hash, -$checksumLength);

        return substr(md5($key), 0, $checksumLength) == $checksum;
    }

    /**
     * Get log message
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Append message to log
     *
     * @param string $message
     * @throws Exception
     */
    protected function addLog($message)
    {
        if (!is_string($message)) {
            throw new Exception('addLog message type must be string');
        }

        $this->log .= PHP_EOL . $message . PHP_EOL;
    }

    /**
     * Set log message
     *
     * @param string $message
     * @throws Exception
     */
    protected function setLog($message)
    {
        if (!is_string($message)) {
            throw new Exception('setLog message type must be string');
        }

        $this->log = $message;
    }

    /**
     * Log to file
     *
     * @access protected
     * @param string $string
     * @throws Exception
     */
    protected function logToFile($string)
    {
        if ($this->logFileDestination) {
            $f = fopen($this->logFileDestination, 'a');
            if (!$f) {
                throw new Exception('SeeMe Gateway: failed to fopen( "' . $this->logFileDestination . '" )');
            }

            if (is_array($string)) {
                foreach ($string as $key => $value) {
                    fputs($f, date("Y-m-d H:i:s") . ' - ' . $key . ' => ' . $value . "\n");
                }
            } else {
                fputs($f, date("Y-m-d H:i:s") . ' - ' . $string . "\n");
            }

            fclose($f);
        }
    }
}