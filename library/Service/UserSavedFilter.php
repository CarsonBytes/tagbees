<?php
class Service_UserSavedFilter{
	protected $identity;
	protected $db;
  protected $table_name = 'user_saved_filter';
	function __construct(){
		  $this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
      //$this->db->getProfiler()->setEnabled(true);
  		if (!Zend_Auth::getInstance()->hasIdentity()){
  			return -1;
  		}
	}
	public function get($name=''){
   $select=$this->db->select()->where('user_id = ?',$this->identity->item_id);
	  if($name == ''){
      $select->from($this->table_name,array('name'));
	  } else {
      $select->from($this->table_name,array('name', 'q',/*'sort_by',*/'is_match_location','is_match_interest','place_lat','place_lng','radius','begin_date','end_date'));
	    $select->where('name = ?',$name);
	  }
		return $this->db->fetchAll($select);
	}
	public function save($name, $q, $is_match_location, $is_match_interest,$place_lat, $place_lng, $radius, $from, $to, $override=0){
		try {
			if ($override == 0){
				$vars=array(
					'user_id'=>$this->identity->item_id,
					'name'=>$name,
          'q'=>$q,
          'is_match_location'=>$is_match_location,
          'is_match_interest'=>$is_match_interest,
          'place_lat'=>$place_lat,
          'place_lng'=>$place_lng,
          'radius'=>$radius,
          'begin_date'=>$from,
          'end_date'=>$to,
					'create_time'=>date('Y-m-d H:i:s')
				);
				$this->db->insert($this->table_name,$vars);
			}else{
				$sql='INSERT INTO '.$this->table_name.'
					(user_id,name, q, place_lat, place_lng,radius,begin_date, end_date, create_time) 
					VALUES';
				$sql.='('.$this->identity->item_id.','. 
						"'".$name."'".','.
            $q.','.
            $is_match_location.','.
            $is_match_interest.','.
            $place_lat.','.
            $place_lng.','.
            $radius.','.
            $from.','.
            $to.','.
						'NOW())';
				$sql.=" ON DUPLICATE KEY UPDATE name=".$name.
                ", q=".$q.
                ", is_match_location=".$is_match_location.
                ", is_match_interest=".$is_match_interest.
                ", place_lat=".$place_lat.
                ", place_lng=".$place_lng.
                ", radius=".$radius.
                ", begin_date=".$from.
                ", end_date=".$to.
				        ", create_time=NOW()";
				$this->db->query($sql);
			}
			return 1;
		} catch (Zend_Db_Exception $e) {
			//return $e->getCode();
			return $e->getMessage();
		}
	}
	public function delete($name){
		try {
			$where=array(
				'user_id=?'=>$this->identity->item_id,
				'name=?'=>$name
			);
			$this->db->delete($this->table_name,$where);
			return 1;
		} catch (Zend_Db_Exception $e) {
			return $e->getCode();
		}
	}
}