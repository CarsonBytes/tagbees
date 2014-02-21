<?php

class Ajax_ImageController extends Zend_Controller_Action
{
	
    public function init()
    {
        if(!$this->getRequest()->isPost()){
            exit(0);
        }else{
            $this->params=$this->_request->getPost();
        }
    }
    
    public function updateInfosAction(){
      
      $array = array();
      if (!Zend_Auth::getInstance()->hasIdentity()){
        return false;
      } else{
        $imageService=new Service_Image();
        $array['result'] = $imageService->updateInfos($this->params);
      }
        
      $this->_helper->json($array);
    }
}

