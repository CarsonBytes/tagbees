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
/*
    public function getUserSavedEventTemplatesAction(){
      $eventTemplateService=new Service_UserSavedEventTemplate();
      $result=$eventTemplateService->save(
        $this->params['name'], 
        $this->params['data']
      );
      $this->_helper->json(array('result'=>$result));
    }
*/
    public function saveEventTemplateAction(){
      $eventTemplateService=new Service_UserSavedEventTemplate();
      $result=$eventTemplateService->save(
        $this->params['name'], 
        $this->params['data']
      );
      $this->_helper->json(array('result'=>$result));
    }
    public function deleteEventTemplateAction(){
      $eventTemplateService=new Service_UserSavedEventTemplate();
      $result=$eventTemplateService->delete(
        $this->params['name']
      );
      $this->_helper->json(array('result'=>$result));
    }
}

