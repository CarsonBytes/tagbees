<?php
class EventController extends Zend_Controller_Action
{
    
    public function preDispatch(){
       
    }
    
    public function indexAction()
    {
        if ($this->slug_name == 'new' || $this->slug_name == 'new_advanced'){
            $this->redirect('event/new');
        }
        
    }
    
    public function newAction()
    {
        
    }
    
    public function newAdvancedAction()
    {
        
    }
}
