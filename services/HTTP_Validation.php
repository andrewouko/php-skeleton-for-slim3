<?php
namespace Services;

use InvalidArgumentException;
use DateTime;
use RuntimeException;
use stdClass;

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
                if(!$metadata->isOptional) throw new \InvalidArgumentException($param . " is required");
            }
            // if($metadata->type == 'numeric') $input->$param = (int) $input->$param;
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
    private function validateParameter(string $type, string $name, bool $isOptional = false, &$val, int $length = null, array $valid_values = []){
        if(is_string($val) || is_numeric($val) || is_bool($val))
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
                $date = DateTime::createFromFormat('Y-m-d', $val);
                if($val && $date->format('Y-m-d') === $val) return true; else throw new InvalidArgumentException($name . " must be a valid date. Value provided: " . $val);
                break;
            case 'boolean':
                if($val && is_bool($val)) return true; else throw new InvalidArgumentException($name . " must be a boolean. Provided: " . gettype($val) . ". Value provided: " . $val);
                break;
            case 'file':
                if($val && isset($val->file) && is_file($val->file)) return true; else throw new InvalidArgumentException($name . " must be a valid file. File property of input is " . (isset($val->file) ? "set." : " not set.") . " File property of input is " . (is_file($val->file) ? " is a valid file." : " not a valid file."));
                break;
            // case 'optional':
            //     if((isset($val) && (gettype($val) != null && !is_null($length) && (strlen($val) <= $length))) || !isset($val)) return true; else throw new \InvalidArgumentException($name . " is optional and should have length of " . $length . ". " . $value_provided);
            //     break;
            default:
                throw new \InvalidArgumentException($name . " is using an unsupported validation type " . $type);
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
                    return $val->isOptional;
                });
            case 'required':
                return array_filter($this->parameters, function($val){
                    return !$val->isOptional;
                });
            default:
                throw new \InvalidArgumentException("Invalid type of paramters to get. Valid types: ". json_encode($types));

        }
    }
    function setParameterMetadata():stdClass{
        //get arguments passed in order
        $arguments = func_get_args();
        if(!count($arguments)) throw new InvalidArgumentException("Atleast one argument is required to setParameterMetadata.");

        // init
        $metadata = array();
        $type = $length = $valid_values = null;
        $indexes_from_length = 0;

        // check whether the argument 1 is set to optional
        if(isset($arguments[0]) && strtower($arguments[0]) == 'optional'){
            $metadata['isOptional'] = true;
            // check whether the 2nd argument is the length or type parameter
            if(isset($arguments[1]) && is_string($arguments[1])){
                // if string it is probably the type
                $type = $arguments[1];
                // cater for additional arguments
                $indexes_from_length++;
            } else {
                // it is probably the length instead hence the type is automatically set
                $type = 'alphanumeric';
                // argument indexes remain the same
            }
        }
        
        if(!$type){
            if(isset($arguments[0])) $type = $arguments[0];
        }

        if(isset($arguments[($indexes_from_length + 1)])) $length = $arguments[($indexes_from_length + 1)];
        if(isset($arguments[($indexes_from_length + 2)])) $valid_values = $arguments[($indexes_from_length + 2)];
        
        //validate the metadata arguments after optional (if set) and assign their values to metadata array
        foreach(['type', 'length', 'valid_values'] as $arg){
            if(is_string($arg))
                $arg = strtolower($arg);
            switch($arg){
                case 'type':
                    if(!isset($type)) throw new RuntimeException("Unable to determine the type of the paramter metadata. The type argument is required.");
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
        return (object) $metadata;
    }
}