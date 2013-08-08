<?php
class UserController extends Zend_Controller_Action
{
    protected $userId;
    
    public function preDispatch(){
       
    }
    
    public function init()
    {   
        if (trim($this->_request->getParam('username'))==""){
            $this->_redirect('users');
        }else{
            $userService=new Service_User();
            $this->userId=
                $userService->getUserIdByUsername(
                    $this->_request->getParam('username')
                );
            if ($this->userId=='')
                //throw new Exception('No such users');
                $this->_redirect('users');
            
            $this->view->user=$userService->get($this->userId, Zend_Db::FETCH_OBJ);
            
            /*if (isset ($this->view->user->profile_pic_filename)) 
                $this->view->profile_pic_url = Common::getUploadedImageUrl($this->user->profile_pic_filename, 'user' );
            else 
                $this->view->profile_pic_url = Common::getUploadedImageUrl(NULL, 'user' );*/
            }
        $this->view->headTitle($this->_request->getParam('username').'\'s Profile - Tagbees');
    }
    
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            $this->_request->getParam('username').'\'s Profile' => null
        );
        
        $actionService = new Service_Action();
        $this->view->logs = $actionService->getActions($this->userId, NULL, Zend_Registry::get('config')->user_feed->rpp);
        
    }
    
    public function interestsAction()
    {
    }
    
    public function bookmarksAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            $this->view->translate('User').' '.$this->_request->getParam('username') =>
                'user/'.$this->_request->getParam('username'),
            'Bookmarks' => null
        );

        $bookmarkService=new Service_Bookmark();
        $this->view->bookmarks=$bookmarkService->getUserBookmarks($this->userId, false);
    }
}
