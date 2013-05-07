<?php
class Service_Auth{
	protected $db;
	function __construct(){
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
    
    
    public function getAllProviders(){
        $select = 
            $this->db->select()
                ->from('account_provider');
        $packed = array();
        foreach ($this->db->fetchAll($select) as $value){
          $packed[$value['id']] = $value['name'];
        }
        return $packed;
    }
    
    public function getUserProviderIdsByUserId($user_id = ''){
      $identity=Zend_Auth::getInstance()->getIdentity();
      $select = 
          $this->db->select()
              ->from('user_account',array('provider_ids'=>new Zend_Db_Expr('GROUP_CONCAT(provider_id)')))
              ->where('user_id = ?',is_int($user_id) ? $user_id : $identity->item_id)
              ->group('user_id');
        return $this->db->fetchOne($select);
    }
    
    public function getAllProviderLinks(){
      $provider_ids = explode(',',$this->getUserProviderIdsByUserId());
        
      $absUrl = 'http://' . $_SERVER['HTTP_HOST'] . Zend_Controller_Front::getInstance()->getRequest()->getRequestUri().'?provider=';
      $authUrl = array();
  
      if (!in_array($this->getProviderIdByName('google'), $provider_ids))
        $authUrl['google'] = TBS\Auth\Adapter\Google::getAuthorizationUrl($absUrl.'google');
      if (!in_array($this->getProviderIdByName('facebook'), $provider_ids))
        $authUrl['facebook'] = TBS\Auth\Adapter\Facebook::getAuthorizationUrl($absUrl.'facebook');
      if (!in_array($this->getProviderIdByName('twitter'), $provider_ids))
        $authUrl['twitter'] = \TBS\Auth\Adapter\Twitter::getAuthorizationUrl($absUrl.'twitter');
        return $authUrl;
    }


    public function addProviderAccount($provider_id, $identifier){
      $identity=Zend_Auth::getInstance()->getIdentity();
      $data = array(
          'user_id' => $identity->item_id,
          'provider_id' => $provider_id,
          'identifier' => $identifier,
          'create_time'=>date('Y-m-d H:i:s')
      );
      return $this->db->insert('user_account',$data);
    }

    public function removeProviderAccount($provider_id, $identifier){
      $identity=Zend_Auth::getInstance()->getIdentity();
      $where = array(
          'user_id = ?' => $identity->item_id,
          'provider_id  = ?' => $provider_id,
          'identifier  = ?' => $identifier
      );
      
      $serviceAuth = new Service_Auth();
      $auth = TBS\Auth::getInstance();
      $auth->clearIdentity($serviceAuth->getProviderIdByName($provider_id));
      
      return $this->db->delete('user_account',$where);
    }
    
}