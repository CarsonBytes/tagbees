<?php

class Ajax_EventController extends Zend_Controller_Action
{
	
    public function init()
    {
        if(!$this->getRequest()->isPost()){
            exit(0);
        }else{
            $this->params=$this->_request->getPost();
        }
    }
    
    public function reminderFormAction(){
      $array = array();
      if (!Zend_Auth::getInstance()->hasIdentity()){
          return false;
      } else{
        $eventService=new Service_Event();
        $array['data'] = $eventService->getReminder($this->params['item_id']);
        $array['result'] = true;
      }
        
      $this->_helper->json($array);
    }

    public function reminderFormSubmitAction(){
      $array = array();
      if (!Zend_Auth::getInstance()->hasIdentity()){
          return false;
      } else{
        $eventService=new Service_Event();
        $array['result'] = $eventService->addReminder($this->params);
        $array['data'] = $eventService->getReminder($this->params['event_reminder_item_id']);
      }
        
      $this->_helper->json($array);
    }

}

