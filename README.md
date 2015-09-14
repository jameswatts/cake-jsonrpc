JSON-RPC Plugin
===============

The **Jsonrpc** plugin for *CakePHP* provides server and client implementations of [JSON-RPC](http://www.jsonrpc.org).

Requirements
------------

* CakePHP 2+
* PHP 5.3+

Installation
------------

To use the plugin simply include it in your application's "app/Plugin" directory, and load it in the "app/Config/bootstrap.php" file.

```php
CakePlugin::load('Jsonrpc');
```

The above code is *not* required if you're already using ```CakePlugin::loadAll()``` to load all plugins.

Implementation
--------------

### Server

The [Jsonrpc.Server](Controller/Component/ServerComponent.php) component allows a *CakePHP* application to listen for incoming **JSON-RPC** calls. The actions listening are defined in the "listen" option of the component's settings. To add the component to your controller acting as the end-point include it in your *$components* property, for example:

```php
public $components = array(
	'Jsonrpc.Server' => array(
		'listen' => array('example') // will process JSON-RPC requests sent to this action
	)
);
```

Once available, the server will now listen on the actions specified in the "listen" setting. When a call is made to one of these actions, and assuming no error occurs previously in the processing of the request, the call will be delegated to the controller method defined in the "method" property of the JSON request object. This method will receive a single argument, which is the JSON request object received by the server. The value returned by this method will be JSON encoded, and sent back to the client in the "result" property of the JSON response object.

```php
public function user($request) {
	if (isset($request->params->userId)) {
		return $this->User->findById($request->params->userId);
	} else {
		throw new Exception('No user ID was specified', 123);
	}
);
```

In order to send an error as the response you need only throw an *Exception* in your controller's method. This will be caught by the **Server** component and processed as a JSON error object.

### Client

The [Jsonrpc.Client](Controller/Component/ClientComponent.php) component allows a *CakePHP* application to make **JSON-RPC** requests to a server. To use the component add it to the *$components* property of your controller, for example:

```php
public $components = array('Jsonrpc.Client');
```

From your actions you can now make requests using the **Client** component. To do so, first create a JSON request object, and then send it to the **JSON-RPC** server.

```php
public function getUser() {
	// create a JSON request object
	$request = $this->Client->createJsonRequest('user', array('userId' => 7));
	// send the request to the server and return the result
	return $this->Client->sendJsonRequest($request, array('host' => 'example.com', 'path' => '/api/call'));
);
```

Keep in mind that if a JSON error object is returned from the server this will be thrown as a *CakeException* in your application.

You can also send batch requests to a server by specifying multiple JSON request objects in an array, for example:

```php
public function getAllTheThings() {
	// create multiple JSON request objects in an array
	$batch = array(
		$this->Client->createJsonRequest('hat', array('hatId' => 11)),
		$this->Client->createJsonRequest('jacket', array('jacketId' => 55)),
		$this->Client->createJsonRequest('shoes', array('shoesId' => 73))
	);
	// send the array of requests to the server and return the results as an array
	return $this->Client->sendJsonRequest($batch);
);
```

When sending batch requests, if one of the request returns a JSON error object a *CakeException* will not be thrown, as the error object is returned within the array. Also, be aware that the order of the JSON response objects may not be coherent with the order of the requests sent, so always use the ID to determine the response corresponding with your request.

Documentation
-------------

For a full reference on the internals of the **JSON-RPC** protocol/transport see the [specification](http://www.jsonrpc.org/specification).

Support
-------

For support, bugs and feature requests, please use the [issues](https://github.com/jameswatts/cake-jsonrpc/issues) section of this repository.

Contributing
------------

If you'd like to contribute new features, enhancements or bug fixes to the code base just follow these steps:

* Create a [GitHub](https://github.com/signup/free) account, if you don't own one already
* Then, [fork](https://help.github.com/articles/fork-a-repo) the [Jsonrpc](https://github.com/jameswatts/cake-jsonrpc) plugin repository to your account
* Create a new [branch](https://help.github.com/articles/creating-and-deleting-branches-within-your-repository) from the *develop* branch in your forked repository
* Modify the existing code, or add new code to your branch, making sure you follow the [CakePHP Coding Standards](http://book.cakephp.org/2.0/en/contributing/cakephp-coding-conventions.html)
* Modify or add [unit tests](http://book.cakephp.org/2.0/en/development/testing.html) which confirm the correct functionality of your code (requires [PHPUnit](http://www.phpunit.de/manual/current/en/installation.html) 3.5+)
* Consider using the [CakePHP Code Sniffer](https://github.com/cakephp/cakephp-codesniffer/tree/1.x) to check the quality of your code
* When ready, make a [pull request](http://help.github.com/send-pull-requests/) to the main repository

There may be some discussion reagrding your contribution to the repository before any code is merged in, so be prepared to provide feedback on your contribution if required.

A list of contributors to the **Jsonrpc** plugin can be found [here](https://github.com/jameswatts/cake-jsonrpc/contributors).

Licence
-------

Copyright 2013 James Watts (CakeDC). All rights reserved.

Licensed under the MIT License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.

Acknowledgements
----------------

Thanks to [Larry Masters](https://github.com/phpnut) and [everyone](https://github.com/cakephp/cakephp/contributors) who has contributed to [CakePHP](http://cakephp.org), helping make this framework what it is today. Also, to the [JSON-RPC Working Group](https://groups.google.com/forum/#!forum/json-rpc), for their hard work and dedication to the specification.

