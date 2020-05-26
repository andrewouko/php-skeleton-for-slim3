<?php
namespace Services;

use InvalidArgumentException;
use DateTime;

class HTTP_Validation {
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
                    if(!isset($metadata->type)) throw new \InvalidArgumentException("Cannot validate this parameter as it does not have a validation type hint");
                    return $this->validateParameter($metadata->type, $param, $input->$param, isset($metadata->length) ? $metadata->length : null, isset($metadata->valid_values) ? $metadata->valid_values : []);
                }
            } else throw new \Exception("Metadata must be an instance of stdClass");
        });
        return true;
    }
    private function validateParameter(string $type, string $name, &$val, int $length = null, array $valid_values = []){
        $value_provided = gettype($val) . " of length " . strlen($val) . " provided. Value provided: " . $val;

        // VAIDATE TYPE
        switch($type){
            case 'alphanumeric':
                if(is_string($val)){}else throw new \InvalidArgumentException($name . " must be a string. " . $value_provided);
                break;
            case 'numeric':
                if(is_int($val) || is_float($val)){}else throw new \InvalidArgumentException($name . " must be an int or float. " . $value_provided);
                break;
            case 'integer':
                $val = (int) $val;
                if(is_int($val)){}else throw new \InvalidArgumentException($name . " must be an integer. " . $value_provided);
                break;
            case 'date':
                $val = DateTime::createFromFormat('Y-m-d', $val);
                if($val && $val->format('Y-m-d') === $val) return true; else throw new InvalidArgumentException($name . " must be a valid date. Value provided: " . $val->format('Y-m-d'));
                break;
            case 'boolean':
                if($val && is_bool($val)) return true; else throw new InvalidArgumentException($name . " must be a boolean. Provided: " . gettype($val) . ". Value provided: " . $val);
                break;
            case 'file':
                if($val && isset($val->$name->file) && is_file($val->$name->file)) return true; else throw new InvalidArgumentException($name . " must be a valid file. File property of input is " . isset($val->$name->file) ? "set." : " not set. File property of input is " . is_file($val->$name->file) ? " is a valid file." : " not a valid file.");
                break;
            case 'optional':
                if((isset($val) && (gettype($val) != null && !is_null($length) && (strlen($val) <= $length))) || !isset($val)) return true; else throw new \InvalidArgumentException($name . " is optional and should have length of " . $length . ". " . $value_provided);
                break;
            default:
                throw new \InvalidArgumentException($name . " is using an unsupported type " . $type);
        }

        // VALIDATE LENGTH
        $length_not_set = (is_null($length) && strlen($val));
        $length_set_string = (is_string($val) && !is_null($length) && strlen($val) <= $length);
        $length_set_numeric = (is_numeric($val) && strlen((string)abs($val)) <= $length);
        if($length_not_set || $length_set_string || $length_set_numeric){}else{
            throw new \InvalidArgumentException($name . " must have a length of " . $length . ". " . $value_provided);
        }

        // VALIDATE VALID VALUES
        if((count($valid_values) < 1 && strlen($val)) || (count($valid_values) && in_array($val, $valid_values))){}else{
            throw new \InvalidArgumentException("Valid values for ". $name . " are: " . json_encode($valid_values));
        }
        return true;
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
        $valid_args = ['type', 'length', 'valid_values'];
        //get arguments passed in order
        $arguments = func_get_args();
        $type = $length = null;
        if(isset($arguments[0])) $type = $arguments[0];
        if(isset($arguments[1])) $length = $arguments[1];
        if(isset($arguments[2])) $valid_values = $arguments[2];
        $metadata = array();
        //validate arguments passed and assign their values to metadata array
        foreach($valid_args as $arg){
            switch($arg){
                case 'type':
                    if(!is_string($type)) throw new \InvalidArgumentException("The argument supplied to the 1st parameter (type) must be a string. Provided: " . gettype($type));
                    if($type) $metadata['type'] = $type;
                    break;
                case 'length':
                    if(isset($length)){
                        if(!is_int($length)) throw new \InvalidArgumentException("The argument supplied to the 2nd parameter (length) must be an int. Provided: " . gettype($length));
                        if($length) $metadata['length'] = $length;
                    }
                    break;
                case 'valid_values':
                    if(isset($valid_values)){
                        if(!is_array($valid_values) && count($valid_values)) throw new \InvalidArgumentException("The argument supplied to the 3rd parameter (valid_values) must be an array and is not empty. Provided: " . gettype($valid_values));
                        if($valid_values) $metadata['valid_values'] = $valid_values;
                    }
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported argument provided. Argument provided: " . $arg);
            }
        }
        return count($metadata) > 0 ? (object) $metadata : null;
    }
}