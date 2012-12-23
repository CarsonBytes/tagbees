<?php

class Ajax_SidebarController extends Zend_Controller_Action
{
	private $params;
    public function openSimplePersonalEventAction(){
        $array = array();
        if (Zend_Auth::getInstance()->hasIdentity()){
            $array['result'] = true;
        } else{
            $array['result'] = false;
        }
        
        $this->_helper->json($array);
    }
    
}

