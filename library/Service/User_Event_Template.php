<?php
class Service_User_Event_Template{
	protected $db;
  protected $_table = 'user_saved_event_template';
  protected $user_id;
	function __construct(){
    if (Zend_Auth::getInstance()->hasIdentity()) {
      $this->user_id = Zend_Auth::getInstance()->getIdentity()->item_id;
    } else {
      return -1;
    }
    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
	public function get($user_id=null){
	  if ($user_id==null) $user_id = $this->user_id;
		$select=$this->db->select()
			->from(array($this->_table),array('name','data'))
			->where ('user_id = ?',$user_id);
		return $this->db->fetchAll($select);
	}
	public function save($name,$data,$user_id = null){
    if ($user_id==null) $user_id = $this->user_id;
		try {
			$sql='INSERT INTO '+$this->_table+'
				(user_id,name,data,create_time,update_time) 
				VALUES';
			$sql.='('.$user_id.','. 
					"'".$name."'".','.
					$data.','.
          'NOW())'.','.
          'NOW())';
			$sql.=" ON DUPLICATE KEY UPDATE name=".$name.", data=".$data.", update_time=NOW()";
			$this->db->query($sql);
			return 1;
		} catch (Zend_Db_Exception $e) {
			return $e->getCode();
			//return $e->getMessage();
		}
	}
	public function delete($name,$user_id = null){
    if ($user_id==null) $user_id = $this->user_id;
		try {
			$where=array(
				'user_id=?'=>$user_id,
				'name=?'=>$name
			);
			$this->db->delete($this->_table,$where);
			return true;
		} catch (Zend_Db_Exception $e) {
			return $e->getCode();
		}
	}
}