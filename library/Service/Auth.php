<?php
class Service_Auth{
	protected $db;
	function __construct(){
		//$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}

    public function getProviderNameById($provider_id){
        $select = 
            $this->db->select()
                ->from('account_provider','name')
                ->where('account_provider.id = ?',$provider_id);
        return $this->db->fetchOne($select);
    }
    
    public function getProviderIdByName($provider_name){
        $select = 
            $this->db->select()
                ->from('account_provider','id')
                ->where('account_provider.name = ?',$provider_name);
        return $this->db->fetchOne($select);
    }
    
}