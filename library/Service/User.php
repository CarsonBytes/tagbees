<?php
class Service_User{
	protected $db;
	private $parameters;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}

	public function get($user_id,$fetch_mode=Zend_Db::FETCH_ASSOC){
		//$this->db->getProfiler()->setEnabled(true);
		$select=
			$this->db->select()
			->from(array('item'),array('*'))
			->where('item.id = ?',$user_id)
			->joinLeft('user','item.id=user.item_id',array('username','no_posts_days', 'score', 'role', 'profile_pic_filename'))
			->joinLeft('membership_level','membership_level.id=user.membership_level_id',array('level_name'=>'name','level_type'=>'type'));

		//get profile picture
		//$imageService=new Service_Image();
		//$select=$imageService->getJoinQuery($select,$user_id, true);
        
		return $this->db->fetchRow($select, array(),$fetch_mode);
	}
    
    public function getUserDataByUsername($username,$fetch_mode=Zend_Db::FETCH_ASSOC){
        //$this->db->getProfiler()->setEnabled(true);
        $select=
            $this->db->select()
            ->from(array('item'),array('*'))
            ->where('user.username = ?',$username)
            ->joinLeft('user','item.id=user.item_id')
            ->joinLeft('membership_level','membership_level.id=user.membership_level_id',array('level_name'=>'name','level_type'=>'type'));

        return $this->db->fetchRow($select, array(),$fetch_mode);
    }
	public function add($username,$password,$email,$gender, $display_name, $display_lang, $provider, $relateds=array(),$related_types=array()){
		//add user item (item -> user)
		try {
			//$commonService=new Common();
			//$slug_username=$commonService->slugUnique($username); // there should not be any duplicate as checked upon submission
            
            $email_template_data->display_name = $display_name;
            $email_template_data->gender = $gender;
            $email_template_data->email = $email;
            $email_template_data->code = md5($email.$username.$password);
            Common::getSession()->user_signup_confirmation->email_template_vars = $email_template_data;

			$packed_data = array (
				'type'=>'user',
				'name'=>$display_name,
				'slug_name'=>$username,
				'create_time'=>date('Y-m-d H:i:s')
			);
			$this->db->insert('item',$packed_data);
			$userId = $this->db->lastinsertid('item','id');
            
			$packed_data = array (
				'item_id' => $userId,
                'username' => $username,
				'email' => $email,
				'gender' => $gender,
                'password' => md5($password),
                'confirm_code' => $email_template_data->code,
				'ip'=>$_SERVER['REMOTE_ADDR'],
				'agent'=>$_SERVER['HTTP_USER_AGENT'],
				'create_time'=>date('Y-m-d H:i:s')
			);
            
            if ($display_lang) $packed_data['display_lang'] = $display_lang;
            
			$this->db->insert('user',$packed_data);

            //add oauth provider connection
            if ($provider){
                $provider_name = $provider->getName();
                $user_identifier = $provider->getId();
                
                $serviceAuth = new Service_Auth();
                $packed_data = array(
                    'user_id' => $userId,
                    'provider_id' => $serviceAuth->getProviderIdByName($provider_name),
                    'identifier' => $user_identifier,
                    'create_time'=>date('Y-m-d H:i:s')
                );
                $this->db->insert('user_account',$packed_data);
            }

			//add new item
			if (!empty($relateds)){
				$itemService=new Service_Item();
				$items=$itemService->bulkAddTags($relateds,$related_types,$userId);
				print_r($items);
				//add interest to user_bookmark
				foreach($items as $item){
					$sql = "INSERT IGNORE INTO user_bookmark (user_id, item_id,type, create_time) VALUES (?, ?, ?, NOW())";
					$vars=array(
						'user_id'=>$userId,
						'item_id'=>$item['id'],
						'type'=>$item['type']
					);
					$this->db->query($sql, $vars);
				}
			}
			return 1;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	public function getUserIdByUsername($username){
		$select = $this->db->select()
							->from('user','item_id')
							->where('username = ?',$username);
		return $this->db->fetchOne($select);
	}
	public function getUsernameByUserId($user_id){
		$select = $this->db->select()
							->from('user','username')
							->where('item_id = ?',$user_id);
		return $this->db->fetchOne($select);
	}
	public function getNameByUserId($user_id){
		$select = $this->db->select()
							->from('user',array('firstname','lastname'))
							->where('item_id = ?',$user_id);
		$result=$this->db->fetchOne($select);
		if ($result!='root'){
			return $result;
		}else{
			return '';
		}
	}
	
	public function saveLanguageSetting($language_code){
    	if (Zend_Auth::getInstance()->hasIdentity()){
            Common::getSession()->user->display_lang = $language_code;
			$this->db->update('user',array('display_lang'=>$language_code),'item_id='.$this->identity->item_id);
		}else{
            Common::getSession()->display_lang = $language_code;
		}
		return true;
	}
	public function getDisplayLang(){
		$select = $this->db->select()
							->from('user',array('display_lang'))
							->where('item_id = ?',$this->identity->item_id);
		$result=$this->db->fetchOne($select);
    	Common::getSession()->user->display_lang=$result;
		return $result;
	}


	public function getUsers($item_ids=array()){
		$select = $this->db->select()
				->from(array('user'),
					array('item_id','firstname','lastname','username',/*'description',*/'feed_type','feed_is_match_interest',/*'address','lat','lng', 'create_time'*/)
					);
					if (!empty($item_ids)){
						$select->where('item_id in (?)',$item_ids);
					}
		$result=$this->db->fetchAll($select);

		$data=array();
    	$interestService=new Service_Bookmark();
		foreach($result as $value){
			$data[$value["id"]]['item_id']=$value["id"];
			$data[$value["id"]]['type']=3;
			$data[$value['id']]['tag']=
				implode(',',$interestService->getUserTags($value["id"],false));
			$data[$value["id"]]["name"]=$value["name"];
			$data[$value["id"]]["username"]=$value["username"];
			$data[$value["id"]]["description"]=$value["description"];
			$data[$value["id"]]["feed_type"]=$value["feed_type"];
			$data[$value["id"]]["feed_is_match_interest"]=$value["feed_is_match_interest"];
			$data[$value["id"]]["address"]=$value["address"];
			$data[$value["id"]]["lat"]=$value["lat"];
			$data[$value["id"]]["lng"]=$value["lng"];
			$data[$value["id"]]["create_time"]=$value["create_time"];
		}
		return $data;
	}
	public function updateLastSeenTime($username){
		$var=array('last_seen_time'=>date('Y-m-d H:i:s'));
        $where['username = ?'] = $username;
		$this->db->update(
			'user',
			$var,
			$where
		);
	}
	public function lock($id,$flag=1){
		$var=array('is_locked'=>$flag);
		$this->db->update(
			'user',
			$var,
			'item_id='.$id
		);
	}
	public function setAdmin($id,$flag=1){
		$var=array('is_admin'=>$flag);
		$this->db->update(
			'user',
			$var,
			'item_id='.$id
		);
	}
    
    public function getUsernameByProvider($provider_id, $identifier){
        $select = 
            $this->db->select()
                ->from('user','username')
                ->joinLeft('user_account', 'user.item_id = user_account.user_id')
                ->where('user_account.provider_id = ?',$provider_id)
                ->where('user_account.identifier = ?',$identifier);
        return $this->db->fetchOne($select);
    }
    
    public function addUserProviderAccount($provider_id, $identifier){
        $data = array(
            'user_id' => $this->identity->item_id,
            'provider_id' => $provider_id,
            'identifier' => $identifier,
            'create_time'=>date('Y-m-d H:i:s')
        );
        return $this->db->insert('user_account',$data);
    }

    public function removeUserProviderAccount($provider_id, $identifier){
        $where = array(
            'user_id = ?' => $this->identity->item_id,
            'provider_id  = ?' => $provider_id,
            'identifier  = ?' => $identifier
        );
        
        $serviceAuth = new Service_Auth();
        $auth = TBS\Auth::getInstance();
        $auth->clearIdentity($serviceAuth->getProviderIdByName($provider_id));
        
        return $this->db->delete('user_account',$where);
    }
    
    public function getUserProviderIdsByUserId($user_id = ''){
        $select = 
            $this->db->select()
                ->from('user_account',array('provider_ids'=>new Zend_Db_Expr('GROUP_CONCAT(provider_id)')))
                ->where('user_id = ?',is_int($user_id) ? $user_id : $this->identity->item_id)
                ->group('user_id');
        return $this->db->fetchOne($select);
    }
    
    public function getUserByConfirmCode($confirm_code = ''){
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = 
            $this->db->select()
                ->from('user')
                ->where('confirm_code = ?', $confirm_code)
                ->joinLeft('item', 'item.id = user.item_id', array('display_name'=>'name'));
        return $this->db->fetchRow($select);
        
    }
    
    public function setIsConfirmedByConfirmCode($confirm_code = '', $is_confirmed = 1){
        $where = array(
            'confirm_code = ?' => $confirm_code
        );
        $this->db->update(
            'user',
            array('is_confirmed'=>$is_confirmed),
            $where
        );
        
    }

    public function getUserByEmail($email){
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = 
            $this->db->select()
                ->from('user')
                ->where('email = ?',$email)
                ->joinLeft('item', 'item.id = user.item_id', array('display_name'=>'name'));
        return $this->db->fetchRow($select);
    }
    
    public function setResetPasswordCode($nonce, $user_id) {
        $where = array(
            'item_id = ?' => $user_id
        );
        $this->db->update(
            'user',
            array(
                'reset_password_code' => $nonce,
                'reset_password_expiry_time' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ),
            $where
        );
    }

    public function isRestorePasswordCodeValid($email, $code){
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = 
            $this->db->select()
                ->from('user', array('item_id'))
                ->where('email = ?',$email)
                ->where('reset_password_code = ?',$code)
                ->where('reset_password_expiry_time > ?',date('Y-m-d H:i:s'))
                ->where('is_confirmed = 1');
        $result = $this->db->fetchRow($select);
        
        if ($result == NULL) return FALSE;
        return true;
    }
    
    public function updatePasswordByEmail($password, $email){
        $where = array(
            'email = ?' => $email
        );
        $this->db->update(
            'user',
            array(
                'password' => md5($password)
            ),
            $where
        );
        return true;
    }
    public function disableResetPasswordToken($email){
        $where = array(
            'email = ?' => $email
        );
        $this->db->update(
            'user',
            array(
                'reset_password_code' => '',
                'password_reset_times' => new Zend_Db_Expr('password_reset_times+1'),
            ),
            $where
        );
        return true;
    }
}