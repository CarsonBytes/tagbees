<?php
class SettingsController extends Zend_Controller_Action
{
    
    public function preDispatch(){
       
    }
    
    public function init()
    {
        $this->view->headTitle('Settings - Tagbees');
    }
    
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            'Settings' => null
        );
    }
}
