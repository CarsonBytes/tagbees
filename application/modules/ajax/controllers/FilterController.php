<?php

class Ajax_FilterController extends Zend_Controller_Action
{
	private $params;
  private $userSavedFilterService;
  public function init()
  {
    $this->params=$this->_request->getParams();
    $this->userSavedLocationService=new Service_UserSavedFilter();
  }
  public function saveAction(){
    $filters = array(
      'is_match_location'   => 'Digits',
      'is_match_interest'   => 'Digits',
      'override'   => 'Digits',
      'begin_time'=>new Zend_Filter_Null(Zend_Filter_Null::STRING),
      'end_time'=>new Zend_Filter_Null(Zend_Filter_Null::STRING)
    );
    $validators = array(
        'override'   => array(),
        'name'   => array(),
        'q'   => array(),
        'is_match_location'   => array(),
        'is_match_interest'   => array(),
        'place_lat'   => array(new Zend_Validate_Float('en_US')),
        'place_lng'   => array(new Zend_Validate_Float('en_US')),
        'radius'   => array(new Zend_Validate_Float('en_US')),
        'begin_date' => array(
          new Zend_Validate_Date(array('format' => 'YYYY-MM-DD'))
        ),
        'end_date' => array(
          new Zend_Validate_Date(array('format' => 'YYYY-MM-DD'))
        ),
    );
    $options = array(
      'allowEmpty' => true
    );
        
    $input = new Zend_Filter_Input($filters, $validators,$this->params, $options);
      
    if ($input->hasInvalid() || $input->hasMissing()) {
        $result = $input->getMessages();
      }else{
        $input_vals = $input->getEscaped();
        $result=$this->userSavedLocationService->save(
          $input_vals['name'], 
          $input_vals['q'],
          //$input_vals['sort_by'], 
          $input_vals['is_match_location'], 
          $input_vals['is_match_interest'], 
          $input_vals['place_lat'], 
          $input_vals['place_lng'], 
          $input_vals['radius'],
          $input_vals['begin_date'], 
          $input_vals['end_date'], 
          $input_vals['override']
        );
    }
    $this->_helper->json(array('result'=>$result));
  }
  public function deleteAction(){
    $result=$this->userSavedLocationService->delete(
        $this->params['name']
    );
    $this->_helper->json(array('result'=>$result));
  }
  public function getAction(){
    if (!isset($this->params['name'])){
      $result = $this->userSavedLocationService->get();
    }else{
      $result = $this->userSavedLocationService->get($this->params['name']);
    }
    $this->_helper->json(array('result'=>$result));
  }

}

