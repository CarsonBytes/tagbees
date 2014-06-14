<?php

class Validate_Tel
    extends Zend_Validate_Abstract
{
    const NOT_PHONE = 'notPhone';
    const INVALID_PHONE = 'invalidCellPhone';
    const STRING_EMPTY = 'stringEmpty';
    const INVALID_LENGTH = 'invalidLength';

    protected $_messageTemplates = array(
        self::NOT_PHONE => "'%value%' is not a phone number",
        self::INVALID_LENGTH => "Please enter 8-digit HK phone number"
        //self::INVALID_PHONE => "'%value%' is not a cell phone number, starting with 123,213 or 231",
        //self::STRING_EMPTY => "please provide a cell phone number"
    );

    public function isValid($value)
    {
        //if (!is_string($value) && !is_int($value))
        if (!is_numeric($value))
        {
            $this->_error(self::NOT_PHONE, $value);
            return false;
        }
        $this->_setValue((string) $value);
    	
        if (strlen($value) != 8)
        {
            $this->_error(self::INVALID_LENGTH);
            return false;
        }

        return true;
    }
}