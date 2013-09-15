<?php
class NewEventController extends Zend_Controller_Action {

  public function preDispatch() {
    if (!Zend_Auth::getInstance() -> hasIdentity()) {
      $this -> _redirect('/auth/login?redirect=' . urlencode($this -> getRequest() -> REQUEST_URI));
      return false;
    }
  }

  public function init() {
    $this -> view -> headTitle('Tagbees - Create Event');
  }

  public function indexAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Create Event' => null);
    
    if( $this->getRequest()->isPost() ){
      
      $data = $this->_request->getParams();
      //echo '<pre>';var_dump($data);echo '</pre>';
      $filters=array(
          'new_event_event_type'   => 'Digits',
          'new_event_name'   => 'StringTrim',
          'new_event_place'   => 'StringTrim',
          'new_event_is_any_time'   => array(
            new Plugins_CheckboxValueFilter()
          ),
          'new_event_is_all_day'   => array(
            new Plugins_CheckboxValueFilter()
          ),
          'new_event_is_free'   => array(
            new Plugins_CheckboxValueFilter()
          )
      );
      $validators = array(
          'new_event_event_type'   => array(
              'digits',
              new Zend_Validate_Between(1, 3),
              'messages' => array(1=>'Please choose the valid event type from our 3 provided event types')
          ),
          'new_event_name'   => array(
              new Zend_Validate_StringLength(
                  array('min' => 4, 'max' => 20, 'encoding'=> 'utf-8')
              )
          ),
          'new_event_place'   => array(),
          'new_event_place_lat'   => array(new Zend_Validate_Float('en_US')),
          'new_event_place_lng'   => array(new Zend_Validate_Float('en_US')),
          'new_event_is_any_time' => array('default' => 0),
          'new_event_is_all_day' => array('default' => 0),
          'new_event_begin_date' => array(
            new Zend_Validate_Date(array('format' => 'YYYY-MM-DD'))
          ),
          'new_event_end_date' => array(
            new Zend_Validate_Date(array('format' => 'YYYY-MM-DD'))
          ),
          'new_event_begin_time' => array(
            new Zend_Validate_Date(array('format' => 'HH:mm'))
          ),
          'new_event_end_time' => array(
            new Zend_Validate_Date(array('format' => 'HH:mm'))
          ),
          //'new_event_is_description_editor_toggled'=> array(),
          'new_event_timezone_id'   => array(),
          'new_event_tree_ids'   => array(),
          'new_event_new_tags' => array(),
          'new_event_tag_ids' => array(),
          'new_event_description'   => array(),
          'new_event_is_free' => array('default' => 0),
          'new_event_min_price' => array(new Zend_Validate_Float('en_US'), 'default' => 0),
          'new_event_max_price' => array(new Zend_Validate_Float('en_US'), 'default' => 0),
          'new_event_submit'   => array(),
          'new_event_save'   => array(),
          'new_event_more_detail'   => array()
      );
      $options = array(
          'missingMessage' => "'%field%' is required",
          'allowEmpty' => true
      );
        
      $input = new Zend_Filter_Input($filters, $validators,$data,$options);
      //echo '<pre>';var_dump($input->getEscaped());echo '</pre>';
      //echo '<pre>';var_dump($input->getMessages());echo '</pre>';die();
      if ($input->hasInvalid() || $input->hasMissing()) {
        $this->_helper-> FlashMessenger(array('error' => 'Please correct the errors and submit the form again'.'.'));
        $this->view->errors = $input->getMessages();
      }else{
        $input_vals = $input->getEscaped();
        
        if (isset($input_vals['new_event_submit'])){
          unset($input_vals['new_event_submit']);
          $postService=new Service_Post();
          $slug_name=$postService->add($input_vals);
          
          $this->_helper->FlashMessenger(array('success'=>'Your event \"'.$input->getEscaped('new_event_name').'\" has been created.'));
          
          $this->_redirect('/event/' . urlencode($slug_name));
        } else if (isset($input_vals['new_event_save'])){
          unset($input_vals['new_event_save']);
          
        } else if (isset($input_vals['new_event_more_detail'])){
          unset($input_vals['new_event_more_detail']);
        } 
      }
    }
  }

  public function advancedAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Create Event (Advanced)' => null);

    if( $this->getRequest()->isPost() ){
      
      $data = $this->_request->getParams();
      //echo '<pre>';var_dump($data);echo '</pre>';
      $filters=array(
          'new_event_event_type'   => 'Digits',
          'new_event_name'   => 'StringTrim',
          'new_event_place'   => 'StringTrim',
          'new_event_is_any_time'   => array(
            new Plugins_CheckboxValueFilter()
          ),
          'new_event_is_all_day'   => array(
            new Plugins_CheckboxValueFilter()
          ),
          'new_event_is_free'   => array(
            new Plugins_CheckboxValueFilter()
          )
      );
      $validators = array(
          'new_event_event_type'   => array(
              'digits',
              new Zend_Validate_Between(1, 3),
              'messages' => array(1=>'Please choose the valid event type from our 3 provided event types')
          ),
          'new_event_name'   => array(
              new Zend_Validate_StringLength(
                  array('min' => 4, 'max' => 20, 'encoding'=> 'utf-8')
              )
          ),
          'new_event_place'   => array(),
          'new_event_place_lat'   => array(new Zend_Validate_Float('en_US')),
          'new_event_place_lng'   => array(new Zend_Validate_Float('en_US')),
          'new_event_application_place'   => array(),
          'new_event_application_place_lat'   => array(new Zend_Validate_Float('en_US')),
          'new_event_application_place_lng'   => array(new Zend_Validate_Float('en_US')),
          'new_event_is_any_time' => array('default' => 0),
          'new_event_is_all_day' => array('default' => 0),
          'new_event_begin_date' => array(
            new Zend_Validate_Date(array('format' => 'YYYY-MM-DD'))
          ),
          'new_event_end_date' => array(
            new Zend_Validate_Date(array('format' => 'YYYY-MM-DD'))
          ),
          'new_event_begin_time' => array(
            new Zend_Validate_Date(array('format' => 'HH:mm'))
          ),
          'new_event_end_time' => array(
            new Zend_Validate_Date(array('format' => 'HH:mm'))
          ),
          //'new_event_is_description_editor_toggled'=> array(),
          'new_event_timezone_id'   => array(),
          'new_event_tree_ids'   => array(),
          'new_event_new_tags' => array(),
          'new_event_tag_ids' => array(),
          'new_event_description'   => array(),
          'new_event_date_note'   => array(),
          'new_event_price_note'   => array(),
          'new_event_traffic_note'   => array(),
          'new_event_application_time_note'   => array(),
          'new_event_is_free' => array('default' => 0),
          'new_event_min_price' => array(new Zend_Validate_Float('en_US'), 'default' => 0),
          'new_event_max_price' => array(new Zend_Validate_Float('en_US'), 'default' => 0),
          'new_event_organiser_name'   => array(),
          'new_event_organiser_email'   => array(),
          'new_event_organiser_tel'   => array(),
          'new_event_organiser_website'   => array(),
          'new_event_organiser_detail'   => array(),
          'new_event_submit'   => array()
      );
      $options = array(
          'missingMessage' => "'%field%' is required",
          'allowEmpty' => true
      );
        
      $input = new Zend_Filter_Input($filters, $validators,$data,$options);
      //echo '<pre>';var_dump($input->getEscaped());echo '</pre>';
      //echo '<pre>';var_dump($input->getMessages());echo '</pre>';die();
      if ($input->hasInvalid() || $input->hasMissing()) {
        $this->_helper-> FlashMessenger(array('error' => 'Please correct the errors and submit the form again'.'.'));
        $this->view->errors = $input->getMessages();
      }else{
        $input_vals = $input->getEscaped();
        
        if (isset($input_vals['new_event_submit'])){
          unset($input_vals['new_event_submit']);
          $postService=new Service_Post();
          $slug_name=$postService->add($input_vals);
          
          $this->_helper->FlashMessenger(array('success'=>'Your event \"'.$input->getEscaped('new_event_name').'\" has been created.'));
          
          $this->_redirect('/event/' . urlencode($slug_name));
        }
      }
    }
  }

}
