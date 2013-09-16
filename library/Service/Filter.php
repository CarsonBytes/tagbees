<?php
class Service_Filter{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		if (!Zend_Auth::getInstance()->hasIdentity()){
			return -1;
		}
	}
	public function getSavedLocations(){
		$select=$this->db->select()
			->from(array('user_saved_location'),array('name','place_lat','place_lng','radius'))
			->where ('user_id = ?',$this->identity->item_id);
		return $this->db->fetchAll($select);
	}
	public function save($name,$place_lat,$place_lng,$radius,$override=0){
		try {
			if ($override==0){
				$vars=array(
					'user_id'=>$this->identity->item_id,
					'name'=>$name,
					'place_lat'=>$place_lat,
					'place_lng'=>$place_lng,
					'radius'=>$radius,
					'create_time'=>date('Y-m-d H:i:s')
				);
				$this->db->insert('user_saved_location',$vars);
			}else{
				$sql='INSERT INTO user_saved_location
					(user_id,name, place_lat, place_lng,radius,create_time) 
					VALUES';
				$sql.='('.$this->identity->item_id.','. 
						"'".$name."'".','.
						$place_lat.','.
						$place_lng.','.
						$radius.','.
						'NOW())';
				$sql.=" ON DUPLICATE KEY UPDATE place_lat=".$place_lat.", place_lng=".$place_lng.", radius=".$radius.", update_time=NOW()";
				$this->db->query($sql);
			}
			return 1;
		} catch (Zend_Db_Exception $e) {
			return $e->getCode();
			//return $e->getMessage();
		}
	}
	public function delete($name){
		try {
			$where=array(
				'user_id=?'=>$this->identity->item_id,
				'name=?'=>$name
			);
			$this->db->delete('user_saved_location',$where);
			return 1;
		} catch (Zend_Db_Exception $e) {
			return $e->getCode();
		}
	}
}