<?php

class Ajax_SearchController extends Zend_Controller_Action
{
	private $params;
	private $common;
	private $db;
    public function init(){
		$this->params=$this->_request->getParams();
		$this->common=new Common();
		$this->db = Zend_Db_Table::getDefaultAdapter();
    }
	public function autocompleteAction()
	{
	  $feedService=new Service_Feed();
    $sql=$this->db->select()
        ->from(
          array('f'=>'item'),array('label'=>'name', 'category'=>'type')
        )
        ->where("f.name LIKE ?", '%'.$this->params['q'].'%')
        ->orWhere("f.place LIKE ?", '%'.$this->params['q'].'%')
        ->order('f.name desc');
		$this->_helper->json($this->db->fetchAll($sql));
	}
}

