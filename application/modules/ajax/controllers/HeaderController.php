<?php

class Ajax_HeaderController extends Zend_Controller_Action
{
	private $params;
    public function openLoginnedPopupAction(){
        $array = array();
        if (Zend_Auth::getInstance()->hasIdentity()){
            $array['result'] = true;
        } else{
            $array['result'] = false;
        }
        
        $this->_helper->json($array);
    }
    
}

