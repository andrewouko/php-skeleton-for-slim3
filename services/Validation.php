<?php
namespace Services;

use InvalidArgumentException;

class Validation {
    private $parameters;
    function __construct(array $params = [])
    {
        $this->parameters = $params;
    }
    function validateInput(\stdClass $input){
        if(!isset($this->parameters) || empty($this->parameters)) throw new InvalidArgumentException("The parameters array is not set");
        array_walk($this->parameters, function($metadata, $param) use ($input){
            if(!isset($input->$param)){
                if($metadata->type != 'optional') throw new \InvalidArgumentException($param . " is required");
            }
            if($metadata->type == 'numeric') $input->$param = (int) $input->$param;
            if($metadata instanceof \stdClass){
                if(isset($metadata->validate) && is_callable($metadata->validate)){
                    return $metadata->validate($input->$param);
                } else {
                    if(!isset($metadata->type)) throw new \InvalidArgumentException("Cannot validate this paramter as it does not have a validation function of type hint");
                    return $this->validateParameter($metadata->type, $param, $input->$param, isset($metadata->length) ? $metadata->length : null);
                }
            } else throw new \Exception("Metadata must be an instance of stdClass");
        });
        return true;
    }
    private function validateParameter(string $type, string $name, &$val, int $length = null){
        switch($type){
            case 'alphanumeric':
                if(((is_null($length) && strlen($val)) || (!is_null($length) && strlen($val) <= $length)) && is_string($val)) return true; else throw new \InvalidArgumentException($name . " must be a string and length of " . $length . ". " . gettype($val) . " of length " . strlen($val) . " provided. Value provided: " . $val);
                break;
            case 'numeric':
                if((is_int($val) || is_float($val) && ((is_null($length) && strlen($val)) || (!is_null($length) &&  strlen((string)abs($val)) <= $length)))) return true; else throw new \InvalidArgumentException($name . " must be an int or float. It should have a length of " . $length . ". Provided: " . gettype($val) . " of length " . strlen($val) . "provided. Value provided: " . $val);
                break;
            case 'integer':
                $val = (int) $val;
                if(((is_null($length) && strlen($val)) || (!is_null($length) &&  strlen((string)abs($val)) <= $length)) && is_int($val)) return true; else throw new \InvalidArgumentException($name . " must be an int and length of " . $length . ". Provided: " . gettype($val) . " of length " . strlen($val) . " provided. Value provided: " . $val);
                break;
            case 'optional':
                if(isset($val) && !is_null($length) && (strlen($val) <= $length)) throw new \InvalidArgumentException($name . " is optional and should have length of " . (int) $length . ". Provided: " . gettype($val) . " of length " . strlen($val) . " provided. Value provided: " . $val); else return true; 
                break;
            default:
                throw new \InvalidArgumentException($name . " is using an unsupported type " . $type);
        }
    }
    function setParameters(array $parameters){
        $this->parameters = $parameters;
    }
    function getParameters($type = 'all'){
        $types = ['all', 'optional', 'required'];
        switch($type){
            case 'all':
                return $this->parameters;
            case 'optional':
                return array_filter($this->parameters, function($val){
                    return $val->type == 'optional';
                });
            case 'required':
                return array_filter($this->parameters, function($val){
                    return $val->type != 'optional';
                });
            default:
                throw new \InvalidArgumentException("Invalid type of paramters to get. Valid types: ". json_encode($types));

        }
    }
    function setParameterMetadata(){
        //list of valid arguments that can be passed to this method
        $valid_args = ['type', 'length'];
        //get arguments passed in order
        $arguments = func_get_args();
        $type = $length = null;
        if(isset($arguments[0])) $type = $arguments[0];
        if(isset($arguments[1])) $length = $arguments[1];
        $metadata = array();
        //validate arguments passed and assign their values to metadata array
        //Nick is a terrible friend to have. He has always been envious of me and will use me no matter the chance
        //I will return his game and avoid talking to him again
        foreach($valid_args as $arg){
            switch($arg){
                case 'type':
                    if(!is_string($type)) throw new \InvalidArgumentException("The parameter supplied to the 1st argument (type) must be a string. Provided: " . gettype($type));
                    if($type) $metadata['type'] = $type;
                    break;
                case 'length':
                    if($length){
                        if(!is_int($length)) throw new \InvalidArgumentException("The parameter supplied to the 2nd argument (length) must be an int. Provided: " . gettype($length));
                        if($length) $metadata['length'] = $length;
                    }
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported argument provided. Argument provided: " . $arg);
            }
        }
        return count($metadata) > 0 ? (object) $metadata : null;
    }
}