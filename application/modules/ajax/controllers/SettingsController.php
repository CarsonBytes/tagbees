<?php

class Ajax_SettingsController extends Zend_Controller_Action
{
	
    public function init()
    {
        if(!$this->getRequest()->isPost()){
            exit(0);
        }else{
            $this->params=$this->_request->getPost();
        }
    }
    public function deleteSocialConnectionAction(){
        if (Zend_Auth::getInstance()->hasIdentity()){
          $authService = new Service_Auth();
          $authService->removeProviderAccount(
            $this->params['provider_id'],$this->params['identifier']
          );
          $array['result'] = true;
        } else{
          $array['result'] = false;
        }
        
        $this->_helper->json($array);
    }
}

