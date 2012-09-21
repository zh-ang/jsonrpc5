<?php

require(dirname(__FILE__)."/../library/Jsonrpc5/include.php");

require(dirname(__FILE__)."/math.class.php");

$server = new Jsonrpc5_Service(new math);
$server->handle();
