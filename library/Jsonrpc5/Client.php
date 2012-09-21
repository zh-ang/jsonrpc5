<?php
/**
 * Jsonrpc5_Client
 * 
 * @package jsonrpc5
 * @author Jay Zhang <i@zh-ang.com>
 * @file Client.php
 * @version 1.0
 * @since 2012-09-20
 * 
 **/

/* $Id$ */

class Jsonrpc5_Client {

    protected $_url     = "";
    protected $_id      = 0;
    protected $_class   = NULL;

    public function __construct($url=NULL) {

        if ($url) $this->_url = "$url";
        $this->_id = 0;
        if (is_null($this->_class)) {
            if (get_class($this) != __CLASS__) {
                $this->_class = get_class($this);
            } else {
                $this->_class = "";
            }
        }

    }

    public function __call($method, $params) {

		$request = array(
            "jsonrpc" => "2.0",
            "method" => ($this->_class?$this->_class.".":"").$method,
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
            throw new Jsonrpc5_Exception("Request error: ".json_encode($response["error"]));
        }
        if (!isset($response["id"])) {
            throw new Jsonrpc5_Exception("Unrecognised package");
        }
        if ($response["id"] != $this->_id) {
            throw new Jsonrpc5_Exception("Incorrect id: req={$req_id}, res={$response["id"]}");
        }

        return $response["result"];
    }

}
