<?php

require(dirname(__FILE__)."/../library/Jsonrpc5/include.php");

$protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "http" : "https";
$url = $protocol."://".$_SEVER["HTTP_HOST"].dirname($_SERVER["REQUEST_URI"])."/server1.php";
$client = new Jsonrpc5_Client($url);
var_dump($client->add(2,3)); // output 5
var_dump($client->mult(2,3)); // output 6

