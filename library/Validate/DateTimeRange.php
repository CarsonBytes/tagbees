<?php

class Validate_DateTimeRange
extends Zend_Validate_Abstract
{
    const dateTimeRangeInvalid = 'dateTimeRangeInvalid';

    protected $_messageTemplates = array(
       self::dateTimeRangeInvalid      => 'incorrect begin and end time range.'
    );
    
    protected $begin_datetime;
    protected $end_datetime;
    
    
    public function __construct($begin_datetime,$end_datetime)
    {
    	if ($begin_datetime!='') $this->begin_datetime = strtotime($begin_datetime);
		if ($end_datetime!='') $this->end_datetime = strtotime($end_datetime); 
    }
    
    public function isValid($value)
    {
    	if(($this->end_datetime=='')||($this->begin_datetime=='')){
    		return true;
    	}
    	
		if ($this->end_datetime>=$this->begin_datetime){
			return true;
		}
    	$this->_error(self::dateTimeRangeInvalid);
    	return false;
    }
}