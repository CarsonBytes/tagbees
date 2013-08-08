<?php

class Plugins_CheckboxValueFilter implements Zend_Filter_Interface
{
    public function filter($value)
    {
        return ($value == 'on') ? 1 : 0;
    }
}

