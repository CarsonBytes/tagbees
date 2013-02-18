<?php

class Ajax_UserController extends Zend_Controller_Action
{
	
    public function init()
    {
        if(!$this->getRequest()->isPost()){
            exit(0);
        }else{
            $this->params=$this->_request->getPost();
        }
    }
    
    public function openUploadThumbnailAction(){
        $array = array();
        if (Zend_Auth::getInstance()->hasIdentity()){
            $array['result'] = false;
        } else{
            $array['result'] = true;
        }
        
        $this->_helper->json($array);
    }

    public function loadMoreBookmarksAction()
    {
        $array = array();
        $bookmarkService=new Service_Bookmark();
        $feed=$bookmarkService->getUserBookmarks($this->params['user_id'],false,$this->params['last_id']);
        $this->_helper->json($feed);
    }

}

