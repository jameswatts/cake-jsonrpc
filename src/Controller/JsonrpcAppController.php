<?php
namespace Jsonrpc\App\Controller;

use Cake\Network\Request;

class JsonrpcAppController extends AppController {

  public function render() {
    $this->request->accepts('application/json');
    die('crap');
  }
}