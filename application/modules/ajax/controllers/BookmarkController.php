<?php

class Ajax_BookmarkController extends Zend_Controller_Action
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
    
    public function openBoxTriggerAction(){
        /*
        $array = array();
                $array['movies'] = array();
                $array['movies'][]=array(
                );*/
        $this->_helper->json(array());
    }

}

