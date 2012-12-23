<?php
class UserController extends Zend_Controller_Action
{
    
    public function preDispatch(){
       
    }
    
    public function init()
    {
        $this->view->headTitle($this->_request->getParam('username').'\'s Profile - Tagbees');
    }
    
    public function indexAction()
    {
        
        Common::getSession()->nav=array(
            'Home' => '/',
            $this->_request->getParam('username').'\'s Profile' => null
        );
        
        $this->view->username = $this->_request->getParam('username');
        
    }
}
