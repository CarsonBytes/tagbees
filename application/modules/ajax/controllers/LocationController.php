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
    	$fLat=$this->params['lat'];
    	$fLon=$this->params['lng'];
		$sql="SELECT id, lat,lng, ( 6371 * acos( cos( radians($fLat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($fLon) ) + sin( radians($fLat) ) * sin( radians( lat ) ) ) ) AS distance FROM item
		having distance < $radius
		ORDER BY distance LIMIT 0 , 20;";
		
		$result=$this->db->fetchAll($sql);
	    
		$this->_helper->json($this->db->fetchAll($sql));
	}
	
	public function saveAction(){
		$locationService=new Service_Location();
		$result=$locationService->save(
			$this->params['name'], 
			$this->params['lat'], 
			$this->params['lng'], 
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

