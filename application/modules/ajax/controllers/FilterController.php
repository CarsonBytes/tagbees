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
    $result=$this->userSavedLocationService->save(
        $this->params['name'], 
        $this->params['q'],
        //$this->params['sort_by'], 
        $this->params['is_match_location'], 
        $this->params['is_match_interest'], 
        $this->params['place_lat'], 
        $this->params['place_lng'], 
        $this->params['radius'],
        $this->params['begin_date'], 
        $this->params['end_date'], 
        $this->params['override']
    );
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

