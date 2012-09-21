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

    const JSONRPC_VERSION = "2.0";

    protected $_registered;
    protected $_registered_object;

    protected function _registerFunction($func) {
        $this->_registered[""][$strFunc] = new ReflectionFunction($func);
        $this->_registered_object[""][$strFunc] = null;
    }

    protected function _registerMethod($method) {
        if (!is_array($method) || count($method) != 2) {
            throw new Jsonrpc5_Exception("Invalid method");
        }
        $class = reset($method);
        $name = end($method);
        $object = null
        switch (gettype($class)) {
            case "object": $rc = ReflectionObject($class);
                           $object = $class;
                           break;
            case "string": $rc = ReflectionClass($class);
                           $t = $rc->getConstructor();
                           if ($t instanceof ReflectionFunctionAbstract) {
                               if ($t->getNumberOfRequiredParameters() > 0) {
                                   throw new Jsonrpc5_Exception("Unexpect __construct() of $class");
                               }
                           }
                           $object = new $class;
                           break;
            default: throw new Jsonrpc5_Exception("Invalid class");
        }

        $strClass = $rc->getName();
        if ($rc->hasMethod($name) && $rc->getMethod($name)->isPublic()) {
            $objRef = $rc->getMethod($name);
            $strName = $objRef->getName();

            $this->_registered[$strClass][$strName] = $objRef;
            $this->_registered_object[$strClass][$strName] = $object;
        } else {
            throw new Jsonrpc5_Exception("Method is not callable (not public)");
        }
    }

    protected function _registerObject($object) {
        $r = new ReflectionObject($object);
        $strClass = $r->getName();
        foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $strName  = $method->getName();
            $this->_registered[$strClass][$strName] = $method;
            $this->_registered_object[$strClass][$strName] = $object;
        }
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
        } catch (Exception $e) {
            $this->_log($e);
            throw new Jsonrpc5_Exception("Register failed: ".$e->getMessage());
        }

    }

    protected function _validateRequest(array $request) {

        if( !isset($request["jsonrpc"]) || $request["jsonrpc"] !== JSONRPC_VERSION ) return FALSE;

        if( !isset($request["method"]) || !is_string($request["method"]) ) return FALSE;
        
        if( isset($request["params"]) && !is_array($request["params"] ) return FALSE;
        
        if( isset($request["id"]) && !is_string($request["id"]) && !is_numeric($request["id"]) && !is_null($request["id"]) ) return FALSE;    

        return TRUE;

    }

    protected function _getReflection($method) {

        if (isset($this->_registered[$method[0]][$method[1]])) {
            $objRef = $this->_registered[$method[0]][$method[1]];
            if ($objRef instanceof ReflectionFunctionAbstract) {
                return $arrMethod;
            }
        }

        return FALSE;

    }

    protected function _getMethod($method) {

        $arrMethod = explode(".", $method);

        if (count($arrMethod) > 2) return FALSE;

        if (count($arrMethod) == 2) {
            if (isset($this->_registered[$arrMethod[0]][$arrMethod[1]])) {
                return $arrMethod;
            } 
        }

        if (count($arrMethod) == 1) {
            $strMethod = reset($arrMethod);
            if (isset($this->_registered[""][$strMethod])) {
                return array("", $strMethod);
            }
            if (count($this->_registered) == 1) {
                $strClass = reset(array_keys($this->_registered));
                if (isset($this->_registered[$strClass][$strMethod])) {
                    return array($strClass, $strMethod);
                }
            }
        }

        return FALSE;
    }

    protected function _getParameter(ReflectionFunctionAbstract $reflection, array $params) {

        $is_assoc = array_keys($params) !== range(0, count($sent)-1);
        $ret = array();

        foreach ($reflection->getParameters() as $i=>$param) {

            $key = ( $is_assoc ? $param->getName () : $i );

            if (isset($params[$key])) {
                $ret[$i] = $params[$key];
            } else {
                if (! $param->isOptional()) {
                    return FALSE;
                }
            }

        }
        return $ret;

    }

    protected function _error($code, $message="", $id=NULL) {
        $arrErr = array(
            "jsonrpc" => JSONRPC_VERSION,
            "error" => array(
                "code" => $code,
                "message" => $message,
            "id" => $id,
        );
        return json_encode($arrErr);
    }

    protected function _result($result, $id=NULL) {
        $arrErr = array(
            "jsonrpc" => JSONRPC_VERSION,
            "result" => $result,
            "id" => $id,
        );
        return json_encode($arrErr);
    }

    protected function _log(Exception $e) {
        // do nothing
        return;
    }

    public function invoke($method, $params) {

        $strClass = $method[0];
        $strMethod = $method[1];

        if (empty($strClass)) {
            // invoke a function
            return call_user_func_array($strMethod, $params);
        } else {
            $object = $this->_registered_object[$strClass][$strMethod];
            return call_user_func_array(array($object, $strMethod), $params);
        }
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

            $reflection = $this->_getReflection($method);

            if ($reflection === FALSE) {
                return $this->_error(-32601, "Method not found.", $id);
            }

            $params = $this->_getParameter($reflection, $jsonrpc["params"]);
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

