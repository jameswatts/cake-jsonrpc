<?php
/**
 * Component for providing a JSON-RPC client.
 *
 * PHP 5
 *
 * Copyright 2013, James Watts (http://github.com/jameswatts)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, James Watts (http://github.com/jameswatts)
 * @link          http://www.jsonrpc.org/specification
 * @package       Jsonrpc.Controller.Component
 * @since         CakePHP(tm) v 3.1.4.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Jsonrpc\Controller\Component;

use Cake\Controller\Component;
// use Cake\Component\Controller;
use Cake\Network\Http\Client;
use Cake\Core\Exception\Exception;
use Cake\Log\Log;

class ClientComponent extends Component {

/**
 * The JSON-RPC protocol version.
 *
 * @var string
 */
	protected $_version = '2.0';

/**
 * The current request count.
 *
 * @var integer
 */
	protected $_requestCount = 1;

/**
 * Processes the response and returns the result.
 *
 * @param string $response The response body.
 * @throws CakeException if an error occurs or the response cannot be parsed as JSON.
 * @return string
 */
	protected function _processJsonResponse($response) {

		$json = json_decode(trim($response));
		if (is_array($json) && count($json) > 0) {
			return $json;
		} else if (is_object($json)) {
			if (isset($json->error)) {
				throw new Exception((string) $json->error->message, (int) $json->error->code);
			} else {
				return $json->result;
			}
		} else {
			if (Configure::read('debug') > 0) {
				debug($response);
			}
			throw new Exception('Internal JSON-RPC response error');
		}
	}

/**
 * Creates a JSON-RPC request object.
 *
 * @param string $method The method to call.
 * @param mixed $params The optional param(s) to send.
 * @return object
 */
	public function createJsonRequest($method, $params = null) {
		$request = (object) [];//new stdClass();
		$request->jsonrpc = $this->_version;
		$request->method = (string) $method;
		$request->params = $params;
		$request->id = $this->_requestCount++;

		$this->log(json_encode($params));

		return $request;
	}

/**
 * Sends a JSON-RPC request. To send a batch request provide an array of request objects.
 *
 * @param string|array $request The request object, or array of request objects.
 * @param array $uri The URI scheme to use.
 * @param array $auth The authentication settings.
 * @param array $header The additional headers to send.
 * @param array $cookies The optional cookies to send.
 * @param string $method The HTTP method to use, defaults to POST.
 * @param boolean $redirect Determines if the request follows redirects.
 * @throws CakeException if the HTTP status code is outside of the 200 range.
 * @return object
 */
	public function sendJsonRequest($request, $uri, $auth = array(), $header = array(), $cookies = array(), $method = 'POST', $redirect = false) {


		$uri = array_merge(array(
			'scheme' => 'http',
			'host' => null,
			'port' => null,
			'user' => null,
			'pass' => null,
			'path' => null,
			'query' => null,
			'fragment' => null
		), $uri);
		$url = http_build_url($uri);

		$data = json_encode($request);

		$this->log($data, 'debug');

		$options = [
			'auth' => array_merge(array(
				'method' => 'Basic',
				'user' => null,
				'pass' => null
			), $auth),
			'headers' => array_merge(array(
				'Connection' => 'close'
			), $header),
			'raw' => null,
			'redirect' => $redirect,
			'cookies' => $cookies,
			'type' => 'json'
		];

		$http = new Client();
		$response = $http->{strtolower($method)}($url, $data, $options);

		# @TODO: Proper Cake 3 Exception Handling
		if ($response->code > 0 && $response->code < 200) {
			throw new Exception('Internal JSON-RPC informational error ' . $response->code);
		} else if ($response->code > 299 && $response->code < 400) {
			throw new Exception('Internal JSON-RPC redirection error ' . $response->code);
		} else if ($response->code > 399 && $response->code < 500) {
			throw new Exception('Internal JSON-RPC client error ' . $response->code);
		} else if ($response->code > 499) {
			throw new Exception('Internal JSON-RPC server error ' . $response->code);
		} else {
			return $this->_processJsonResponse($response->body);
		}
	}

}

