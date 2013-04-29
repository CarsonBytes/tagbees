<?php
class Service_Setting{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
	/*
	 * get user's saved weather location(s)
	 */
	public function get($id=''){
		if ($id=='') $id=$this->identity->item_id;
		$select = $this->db->select()
				->from(
					array('user')
              	)
              	->joinLeft('item', 'item.id=user.item_id', array('name','description','place','lat','lng','zoom'))
              	->joinLeft('user_account','user_account.user_id = user.item_id',array('provider_ids'=>new Zend_Db_Expr('GROUP_CONCAT(provider_id)'),'identifiers'=>new Zend_Db_Expr('GROUP_CONCAT(identifier)')))
              	->where('item_id= ?',$id)
                ->group('user.item_id');
       return $this->db->fetchRow($select);
	}

	/*
	 * add a weather location to user. Max: 3 locations only
	 */
	public function add($location){
		$locations=$this->get();
		//var_dump($locations);exit();
		$locations[]=$location;
		$this->saveToUser($locations);
	}

	/*
	 * remove an added weather location from user. Min: 0 location
	 */
	public function remove($location){
		$locations=$this->get();
		array_pop($locations);
		$this->saveToUser($locations);
	}
	
	/*
	 * update a single weather location as others
	 */
	public function update($old_location, $new_location){
		$locations=$this->get();
		foreach($locations as &$value){
			if ($value['country']=='GR'){
				$value['country']='CN';
				break;
			}
		}
		$this->saveToUser($locations);
	}
	
	/*
	 * check if the weather data is updated enough from db, to prevent from fetching too frequently from wunderground. Default: an hour
	 */
	public function checkIfUpdated($country='CN', $city='Hong Kong'){
		$city = str_replace(' ','_',$city);
		$select = $this->db->select()
				->from(array('weather'), 
					array('city')
              		)
              	->where('country = ?',$country)
              	->where('city = ?',$city)
              	->where('update_time > (NOW() - INTERVAL 1 HOUR)');
        
        if (!$this->db->fetchOne($select)){
			$this->fetch($country, $city);  	
        	var_dump('fetch!');exit();
        }else{
        	var_dump('not fetch!');exit();
        }
	}
	
	/*
	 * fetch new weather data of speicifed country and city from wunderground api, and save weather to db
	 */
	private function fetch($country, $city){
		$data = file_get_contents('http://api.wunderground.com/api/3e904b5858a7a66e/geolookup/conditions/forecast/q/'.$country.'/'.$city.'.json');
		$where=array(
			'country=?' => $country,
			'city=?' => $city
		);
		$values=array(
			'country' => $country,
			'city' => $city,
			'data' => $data
		);
		
		$commonService=new Common();
		$commonService->insertReplace('weather',$where,$values);
	}
	
	/*
	 * fetch new weather data of speicifed country and city from wunderground api, and save to db
	 */
	private function saveToUser($locations){
       	$this->db->update(
       		'user', 
       		array('weather_locations'=>json_encode($locations)), 
       		'item_id='.$this->identity->item_id
       	);
	}
	
	public function save($data){
	    // birthday is not needed
	    /*if (isset($data['birth_date']))
	        $data['birth_date']=Common::spaceToNull($data['birth_date']);*/
        
	    $item_table_col_names=array('name','description','place','lat','lng','zoom');
        $user_table_col_names = array(
            'gender', 'privacy_gender', 'privacy_place', 'timezone_id', 'privacy_bookmark_add', 'privacy_event_add'
            /*, 'email', 'homepage', 'birth_date', 'privacy_birth_date', 'privacy_homepage' */
        );
        $item_data=array();
        $user_data=array();
        foreach($data as $key=>$value){
            if (in_array($key, $item_table_col_names)){
                $item_data[$key] = $value;
            }else if (in_array($key, $user_table_col_names)){
                $user_data[$key] = $value;
            }
        }
			
		$result = $this->db->update(
    		  'item', 
    		  $item_data, 
    		  'id='.$this->identity->item_id
    	  );
       	$result = $this->db->update(
       		'user', 
       		$user_data, 
       		'item_id='.$this->identity->item_id
       	);
	}
}