<?php
class Service_Auth{
	protected $db;
	function __construct(){
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
  
  public function validateLogin($username,$password){
    $select = 
      $this->db->select()
          ->from('user')
          ->where('username = ?',$username)
          ->where('password = ?',MD5($password));
   return $this->db->fetchOne($select);
  }
    
}