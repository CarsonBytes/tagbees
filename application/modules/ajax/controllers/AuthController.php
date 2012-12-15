<?php

class Ajax_AuthController extends Zend_Controller_Action
{
	protected $feedService;
	private $params;
    public function init()
    {
		if(!$this->getRequest()->isPost()){
			exit(0);
		}else{
			$this->params=$this->_request->getPost();
		}
	    //$this->feedService=new Service_Feed();
    }
    
    public function openLoginBoxAction(){
        $array = array();
        if (Zend_Auth::getInstance()->hasIdentity()){
            $array['result'] = false;
        } else{
            // Normal login page
            $array['result'] = true;
            $array['data'] = array(
                'googleAuthUrl'=> TBS\Auth\Adapter\Google::getAuthorizationUrl(),
                'facebookAuthUrl'=> TBS\Auth\Adapter\Facebook::getAuthorizationUrl(),
                'twitterAuthUrl'=> TBS\Auth\Adapter\Twitter::getAuthorizationUrl()
            );
        }
        
        $this->_helper->json($array);
    }

}

