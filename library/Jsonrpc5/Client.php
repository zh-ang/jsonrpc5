<?php
/**
 * Jsonrpc5_Client
 * 
 * @package jsonrpc5
 * @author Jay Zhang <jay@easilydo.com>
 * @file Client.php
 * @version 1.0
 * @since 2012-09-20
 * 
 **/

/* $Id$ */

class Jsonrpc5_Client {

    protected $_url;
    protected $_id;

    public function __construct($url) {

        $this->_url = "$url";
        $this->_id = 0;

    }

    public function __call($method, $params) {

		$request = array(
            "jsonrpc" => "2.0",
            "method" => $method,
            "params" => $params,
            "id" => ($this->_id = mt_rand()),
        );

		$opts = array (
            "http" => array (
                "method"  => "POST",
                "header"  => "Content-type: application/json",
                "content" => json_encode($request),
            )
        );

        $raw = file_get_contents($this->_url, FALSE, stream_context_create($opts));
        $response = json_decode($raw, TRUE);

        if (isset($response["error"])) {
            throw new Jsonrpc_Exception("Request error: ".json_encode($response["error"]));
        }
        if (!isset($response["id"])) {
            throw new Jsonrpc_Exception("Unrecognised package");
        }
        if ($response["id"] != $this->_id) {
            throw new Jsonrpc_Exception("Incorrect id: req={$req_id}, res={$response["id"]}");
        }

        return $response["result"];
    }

}
