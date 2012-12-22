<?php

class Ajax_UserController extends Zend_Controller_Action
{
	
    public function openUploadThumbnailAction(){
        $array = array();
        if (Zend_Auth::getInstance()->hasIdentity()){
            $array['result'] = false;
        } else{
            $array['result'] = true;
        }
        
        $this->_helper->json($array);
    }

}

