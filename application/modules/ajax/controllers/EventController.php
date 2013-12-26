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
        //echo '<pre>';var_dump($this->params);echo '</pre>';die();
        $prefix = 'event_reminder_';
        /*$data = array(
          'item_id' => $this->params[$prefix.'event_id'],
          'title' => $this->params[$prefix.'title'],
          'description' => $this->params[$prefix.'description'],
          'tags' => $this->params[$prefix.'tags'],
          'has_email_alarm' => in_array('email', $this->params[$prefix.'options_type']) ? 1 : 0,
          'has_popup_alarm' => in_array('popup', $this->params[$prefix.'options_type']) ? 1 : 0,
          'has_mobile_alarm' => in_array('mobile', $this->params[$prefix.'options_type']) ? 1 : 0,
          'alarm_time' => json_encode(
            array(
              'time'=>$this->params[$prefix.'options_type_time'],
              'time_unit'=>$this->params[$prefix.'options_type_time_unit']
            )
          )
        );*/
        $eventService=new Service_Event();
        $array['result'] = $eventService->addReminder($this->params);
      }
        
      $this->_helper->json($array);
    }

}

