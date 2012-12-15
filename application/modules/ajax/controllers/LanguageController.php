<?php

class Ajax_LanguageController extends Zend_Controller_Action
{
    public function init()
    {
		$this->params=$this->_request->getParams();
    }
	
    public function switchAction()
    {
		$user = new Service_User();
		$array["result"]=$user->saveLanguageSetting($this->params['display_lang']);
    	$this->_helper->json($array);
    }

}

