<?php

class Ajax_DialogController extends Zend_Controller_Action
{
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
		$locationService=new Service_Location();
    	$this->view->user_saved_location=$locationService->getSavedLocations();
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
        $provider_ids = explode(',',$serviceAuth->getUserProviderIdsByUserId());
        
        if (!in_array($serviceAuth->getProviderIdByName('google'), $provider_ids))
            $this->view->googleAuthUrl = TBS\Auth\Adapter\Google::getAuthorizationUrl($this->view->serverUrl() . $this->view->baseUrl('setting?provider=google'));
        if (!in_array($serviceAuth->getProviderIdByName('facebook'), $provider_ids))
            $this->view->facebookAuthUrl = TBS\Auth\Adapter\Facebook::getAuthorizationUrl($this->view->serverUrl() . $this->view->baseUrl('setting?provider=facebook'));
        if (!in_array($serviceAuth->getProviderIdByName('twitter'), $provider_ids))
            $this->view->twitterAuthUrl = \TBS\Auth\Adapter\Twitter::getAuthorizationUrl($this->view->serverUrl() . $this->view->baseUrl('setting?provider=twitter'));
        
    }
}

