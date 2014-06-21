<?php

class Ajax_DialogController extends Zend_Controller_Action
{
  private $params;
	public function init()
    {
		//$this->identity=Zend_Auth::getInstance()->getIdentity();
		$this->params=$this->_request->getParams();
	    //$this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function saveLocationAction(){
      $this->view->title=$this->params['title'];
    }
    public function manageLocationAction(){
    $locationService=new Service_UserSavedLocation();
      $this->view->user_saved_location=$locationService->getSavedLocations();
    }
    public function saveFilterAction(){
      $this->view->title=$this->params['title'];
    }
    public function saveEventTemplateAction(){
      $this->view->title=$this->params['title'];
    }
    public function showItemBookmarksListAction(){
		$bookmarkService=new Service_Bookmark();
    	$this->view->item_likes_list=$bookmarkService->getItemBookmarksList($this->params['id']);
    }
    public function addTagsAction(){
    }
    public function addPersonalEventAction(){
    }
    public function addTreeAction(){
    }
    public function beforeItemSavedAction(){
    }
    public function eventEditedAction(){
    }
    public function eventAddedAction(){
    }
    public function eventReminderAction(){
    }
    public function uploadUserThumbnailAction(){
    }
    public function uploadEventPicsAction(){
    }
    public function addLoginsAction(){
        $serviceAuth = new Service_Auth();
        $this->view->authUrls = $serviceAuth->getAllUserProviderLinks($this->view->baseUrl('setting'));
        
    }
}

