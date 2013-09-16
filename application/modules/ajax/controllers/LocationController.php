<?php

class Ajax_LocationController extends Zend_Controller_Action
{
	private $params;
	private $common;
	private $db;
    public function init(){
		$this->params=$this->_request->getParams();
		$this->common=new Common();
		$this->db = Zend_Db_Table::getDefaultAdapter();
    }
	public function rangeItemsAction()
	{
    	//$fLat='22.33';
    	//$fLon='114.19';
    	$radius=$this->params['radius'];
    	$fLat=$this->params['place_lat'];
    	$fLon=$this->params['place_lng'];
		$sql="SELECT id, place_lat,place_lng, ( 6371 * acos( cos( radians($fLat) ) * cos( radians( place_lat ) ) * cos( radians( place_lng ) - radians($fLon) ) + sin( radians($fLat) ) * sin( radians( place_lat ) ) ) ) AS distance FROM item
		having distance < $radius
		ORDER BY distance LIMIT 0 , 20;";
		
		$result=$this->db->fetchAll($sql);
	    
		$this->_helper->json($this->db->fetchAll($sql));
	}
	
	public function saveAction(){
		$locationService=new Service_Location();
		$result=$locationService->save(
			$this->params['name'], 
			$this->params['place_lat'], 
			$this->params['place_lng'], 
			$this->params['radius'],
			$this->params['override']
		);
		$this->_helper->json(array('result'=>$result));
	}
	public function deleteAction(){
		$locationService=new Service_Location();
		$result=$locationService->delete(
			$this->params['name']
		);
		$this->_helper->json(array('result'=>$result));
	}
}

