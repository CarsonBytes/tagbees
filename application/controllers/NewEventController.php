<?php
class NewEventController extends Zend_Controller_Action
{
    
  public function preDispatch() {
    if (!Zend_Auth::getInstance() -> hasIdentity()) {
      $this -> _redirect('/auth/login?redirect=' . urlencode($this -> getRequest() -> REQUEST_URI));
      return false;
    }
  }

    
    public function init()
    {
        $this->view->headTitle('Tagbees - Create Event');
    }
    
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            'Create Event' => null
        );
        
    }
    
    public function advancedAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            'Create Event (Advanced)' => null
        );
        
        
    }
}
