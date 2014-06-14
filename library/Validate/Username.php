<?php

class Validate_Username
extends Zend_Validate_Abstract
{
    const INVALID      = 'alnumInvalid';
    const NOT_ALNUM    = 'notAlnum';
    const STRING_EMPTY = 'alnumStringEmpty';

    protected $_messageTemplates = array(
       //self::INVALID      => "Invalid type given. String, integer or float expected",
       self::NOT_ALNUM    => "username must contain only 0-9, A-Z, a-z, '.', '_' and '-'",
         self::STRING_EMPTY => "'%value%' is an empty string",
    );
    
    public function isValid($value)
    {
    	if ($value==''){
    		$this->_error(self::STRING_EMPTY);
    		return false;
    	}
    	if(preg_match('/^[A-Za-z0-9._-]+$/', $value)){
    		return true;
    	}else{
    		$this->_error(self::NOT_ALNUM);
    		return false;
    	}
        
    }
}