<?php

class Ajax_BookmarkController extends Zend_Controller_Action
{
	protected $bookmarkService;
	private $params;
    public function init()
    {
  		if(!$this->getRequest()->isPost()){
  			exit(0);
  		}else{
  			$this->params=$this->_request->getPost();
        $this->bookmarkService=new Service_Bookmark();
  		}
    }
    
    public function openBoxTriggerAction(){
        $this->_helper->json(array());
    }

    public function getUserBookmarkListAction()
    {
      $array["result"] = $this->bookmarkService->getUserBookmarkList($this->params['user_id']);
      $this->_helper->json($array);
    }
    
    public function getHighlightsAction()
    {
      $this->_helper->json($this->bookmarkService->getHighlights());
    }
}

