<?php
/**
 * Jsonrpc5_Service
 * 
 * @package jsonrpc5
 * @author Jay Zhang <i@zh-ang.com>
 * @file Service.php
 * @version 1.0
 * @since 2012-09-19
 * 
 **/

/* $Id$ */

class Jsonrpc5_Service {

    protected $_registered;
    protected $_registered_object;

    protected function _registerFunction($func) {
        $_registered[$strFunc] = new ReflectionFunction($func);
    }

    protected function _registerMethod($method) {
        if (!is_array($method) || count($method) != 2) {
            throw new Jsonrpc5_Exception("Invalid method");
        }
        $r = new ReflectionMethod(reset($method), end($method));
        if (!is_callable($method)) {
            throw new Jsonrpc5_Exception("Method is not callable (not public)");
        }
        $this->_registered[$r->getDeclaringClass()][$r->getName()] = $r;
    }

    protected function _registerObject($object) {
        $r = new ReflectionObject($object);
        $strClass = $r->getName();
        foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $this->_registered[$strClass][$method->getName()] = $method;
        }
        $this->_registered_object[$strClass] = $object;
    }

    public function __constrcut ($target=NULL) {

        $this->_registered = array();
        $this->_registered_object = array();
        if ($target) $this->register($target);

    }
    
    public function register($target) {

        try {
            switch (gettype($target)) {
                case "object": $this->_registerObject($target); break;
                case "string": $this->_registerFunction($target); break;
                case "array": $this->_registerMethod($target); break;
                case "NULL": return;
                default: throw new Jsonrpc5_Exception("Invalid type");
            }
        } catch (ReflectionException $e) {
            throw new Jsonrpc5_Exception("Register failed: ".$e->getMessage());
        }

    }

    protected function _validateRequest(array $request) {

        if( !isset($request["jsonrpc"]) || $request["jsonrpc"] !== "2.0" ) return FALSE;

        if( !isset($request["method"]) || !is_string($request["method"]) ) return FALSE;
        
        if( isset($request["params"]) && !is_array($request["params"] ) return FALSE;
        
        if( isset($request["id"]) && !is_string($request["id"]) && !is_numeric($request["id"]) && !is_null($request["id"]) ) return FALSE;    

        return TRUE;

    }

    protected function _getMethod($method) {
        return FALSE;
    }

    protected function _getParameter(ReflectionFunctionAbstract $reflection, array $params) {

        return $params

    }

    protected function _error($code, $message="", $id=NULL) {
        $arrErr = array(
            "jsonrpc" => "2.0",
            "error" => array(
                "code" => $code,
                "message" => $message,
            "id" => $id,
        );
        return json_encode($arrErr);
    }

    protected function _result($result, $id=NULL) {
        $arrErr = array(
            "jsonrpc" => "2.0",
            "result" => $result,
            "id" => $id,
        );
        return json_encode($arrErr);
    }

    protected function _log(Exception $e) {
        // do nothing
        return;
    }

    public function dispatch($request) {
        
        try {

            $jsonrpc = json_decode($request, TRUE);
            if (empty($jsonrpc) || !is_array($jsonrpc)) {
                return $this->_error(-32700, "Parse error.");
            }

            if (!$this->_validateRequest($jsonrpc)) {
                return $this->_error(-32600, "Invalid Request.");
            }

            $id = $jsonrpc["id"];

            $method = $this->_getMethod($jsonrpc["method"]);

            if ($method === FALSE) {
                return $this->_error(-32601, "Method not found.", $id);
            }

            $params = $this->_getParameter($method, $jsonrpc["params"]);
            if ($params === FALSE) {
                return $this->_error(-32602, "Invalid params.", $id);
            }

            $ret = $this->invoke($method, $params);

            return ($id) ? $this->_result($ret) : "";
        } catch (Jsonrpc5_Exception $e) {
            return $this->_error($e->getCode(), $e->getMessage);
        } catch (Exception $e) {
            $this->_log($e);
            return $this->_error(-32603, "Internal error.");
        }

    }

    public function invoke() {
    }

    public function handle() {

        if (!isset($_SERVER["REQUEST_METHOD"]) || $_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new Jsonrpc5_Exception("method was wrong (need POST)");
        }

        if (headers_sent()) {
            throw new Jsonrpc5_Exception("headers has sent");
        }

        header("Content-Type: application/json; charset=utf-8");

        echo $this->dispatch(file_get_contents("php://input"));

        exit;

    }

}

