<?php
class UserController extends Zend_Controller_Action
{
    
    public function preDispatch(){
       
    }
    
    public function init()
    {   
        if (trim($this->_request->getParam('username'))==""){
            $this->_redirect('users');
        }else{
            $userService=new Service_User();
            $this->view->userId=
                $userService->getUserIdByUsername(
                    $this->_request->getParam('username')
                );
            if ($this->view->userId=='')
                //throw new Exception('No such users');
                $this->_redirect('users');
            
            $this->view->user=$userService->get($this->view->userId, Zend_Db::FETCH_OBJ);
            
            /*if (isset ($this->view->user->profile_pic_filename)) 
                $this->view->profile_pic_url = Common::getUploadedImageUrl($this->user->profile_pic_filename, 'user' );
            else 
                $this->view->profile_pic_url = Common::getUploadedImageUrl(NULL, 'user' );*/
            }
        $this->view->headTitle($this->_request->getParam('username').'\'s Profile - Tagbees');
    }
    public function findAction(){
        Common::getSession()->nav=array(
            'Home' => '/',
            'Find User' => NULL
        );
    }
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            $this->_request->getParam('username').'\'s Profile' => null
        );
        
        $logService = new Service_Log();
        $this->view->logs = $logService->getActions($this->view->userId, NULL, Zend_Registry::get('config')->user_feed->rpp);
        
    }
    public function interestsAction()
    {
    }
    public function bookmarksAction()
    {
    }
}
