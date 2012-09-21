<?php

require(dirname(__FILE__)."/../library/Jsonrpc5/include.php");

class math extends Jsonrpc5_Client {
    protected $_url="http://YOUR_HOST_NAME/jsonrpc5/sample/server2.php";
}

$o = new math;
var_dump($o->add(2,3)); // output 5
var_dump($o->mult(2,3)); // output 6

class any_name_you_want extends Jsonrpc5_Client {
    protected $_url="http://YOUR_HOST_NAME/jsonrpc5/sample/server2.php";
    protected $_class="sample"; // will find the class[sample] on server side
}

$p = new any_name_you_want;
var_dump($p->greet());
