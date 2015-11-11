<?php

/**
 * Component for providing a JSON-RPC server.
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
use Cake\Event\Event;
use Cake\Log\Log;

class ServerComponent extends Component {

/**
 * Determines the controllers action on which the server will listen.
 *
 * @var mixed
 */
	public $listen = null;

/**
 * The current controller object.
 *
 * @var Controller
 */
	protected $_controller = null;

/**
 * The JSON-RPC protocol version.
 *
 * @var string
 */
	protected $_version = '2.0';

	// public function initialize(Cake\Controller\Component $config) {
	// 	parent::initialize($config);
	// 	$this->loadComponent('RequestHandler');
	// }

/**
 * Returns a abase JSON-RPC error object.
 *
 * @param int $code The error code.
 * @param string $message The error message.
 * @param int $id The optional JSON-RPC request ID.
 * @return object
 */
	protected function _createJsonError($code, $message, $id = null) {
		$object = (object) [];
		$object->jsonrpc = $this->_version;
		$object->error = (object) [];
		$object->error->code = $code;
		$object->error->message = $message;
		$object->id = $id;
		return $object;
	}

/**
 * Returns a parse error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createParseError($request = null) {
		return $this->_createJsonError(-32700, 'Parse error', null);
	}

/**
 * Returns a request error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createRequestError($request = null) {
		return $this->_createJsonError(-32600, 'Invalid Request', null);
	}

/**
 * Returns a method error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createMethodError($request = null) {
		return $this->_createJsonError(-32601, 'Method not found', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns a params error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createParamsError($request = null) {
		return $this->_createJsonError(-32602, 'Invalid params', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns an internal error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createInternalError($request = null) {
		return $this->_createJsonError(-32000, 'Server error', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns a server error.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createServerError($request = null) {
		return $this->_createJsonError(-32603, 'Internal error', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns an application error.
 *
 * @param object $request The JSON-RPC request object.
 * @param integer $code The error code.
 * @param string $message The error message.
 * @return object
 */
	protected function _createApplicationError($request = null, $code = 0, $message = 'Unknown error') {
		return $this->_createJsonError((int) $code, (string) $message, (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Process the JSON-RPC request.
 *
 * @return array|object|null
 */
	protected function _processJsonRequest() {

		// debug($this->request); exit;

		// debug($this->request->input());
		// exit;

		$input = trim($this->request->input());
		$data = json_decode($input);

		// debug($input);
		// exit;

		if (is_array($data)) {
			return $data;
		} else if (is_object($data)) {
			return $data;
		} else {
			return null;
		}
	}

/**
 * Parses a JSON-RPC request object.
 *
 * @param object $request The JSON-RPC request object.
 * @return array
 */
	protected function _parseJsonRequest($request) {

		// debug($request);

		if (!is_object($request)) {
			return $this->_createParseError($request);
		}
		if (!isset($request->jsonrpc) || !is_string($request->jsonrpc) || $request->jsonrpc !== $this->_version) {
			return $this->_createRequestError($request);
		}
		if (!isset($request->method) || !is_string($request->method) || !method_exists($this->_controller, $request->method)) {
			return $this->_createMethodError($request);
		}
		if (isset($request->params) && !is_array($request->params) && !is_object($request->params)) {
			return $this->_createParamsError($request);
		}
		try {
			ob_start();
			$result = call_user_func_array(array($this->_controller, $request->method), array($request));
			ob_end_clean();
			return $this->_processJsonResponse($request, $result);
		} catch(Exception $e) {
			return $this->_createApplicationError($request, $e->getCode(), $e->getMessage());
		}
	}

/**
 * Returns a JSON-RPC response object.
 *
 * @param object $request The JSON-RPC request object.
 * @param mixed $result The result to send.
 * @return object
 */
	protected function _processJsonResponse($request, $result) {
		$object = new stdClass();
		$object->jsonrpc = $this->_version;
		$object->result = $result;
		$object->id = (isset($request) && is_object($request) && isset($request->id))? $request->id : null;
		return $object;
	}

/**
 * Startup callback, called after Controller::beforeFilter().
 *
 * @param Controller $controller Controller with components to beforeRender.
 * @return void
 */
	public function startup($controller) {

		// debug(get_class_methods($controller->subject())); exit;
		// debug(get_class_methods($controller));exit;

		// debug($controller); exit;
		// pr($this); exit;

		$this->_controller = $controller;

		// debug(get_class_methods($this->_controller)); exit;
		// debug($this->_controller->request->config());

		// debug($this->_controller); exit;

		if (!empty($this->config('listen')) && ((is_string($this->config('listen')) && $this->config('listen') == $this->request->action) || (is_array($this->config('listen')) && in_array($this->request->action, $this->config('listen'))))) {

			Log::write('debug', 'Request: '.json_encode($this->request));

			if($this->request->is('post') || $this->request->is('get')) {
				$this->response->statusCode(200);
				$requests = $this->_processJsonRequest();
				// debug($requests);
				// die('cool');

				if (is_object($requests)) {
					$this->response->body(json_encode($this->_parseJsonRequest($requests)));
				} else if (is_array($requests)) {
					$responses = array();
					for ($i = 0; $i < count($requests); $i++) {
						$responses[] = $this->_parseJsonRequest($requests[$i]);
					}
					$this->response->body(json_encode($responses));
				} else {
					$this->response->body(json_encode($this->_parseJsonRequest($requests)));
				}
			} else {
				$this->response->statusCode(405);
			}

			// debug(get_class_methods($this->response)); exit;

			$this->response->send();
			$this->response->stop();
		}
	}

}

