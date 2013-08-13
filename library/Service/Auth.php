<?php
class Service_Auth{
	protected $db;
	function __construct(){
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
    
  public function getAllProviders(){
      /*$select = 
          $this->db->select()
              ->from('account_provider');
      $packed = array();
      foreach ($this->db->fetchAll($select) as $value){
        $packed[$value['id']] = $value['name'];
      }
      return $packed; */
      return array('google','facebook','twitter');
  }
    
  public function getUserProvidersByUserId($user_id = ''){
    $identity=Zend_Auth::getInstance()->getIdentity();
    $select = 
        $this->db->select()
            ->from('user_account',array('providers'=>new Zend_Db_Expr('GROUP_CONCAT(provider)')))
            ->where('user_id = ?',is_int($user_id) ? $user_id : $identity->item_id)
            ->group('user_id');
      return $this->db->fetchOne($select);
  }
  
  public function getAllUserProviderLinks($absUrl=''){
    $user_providers = explode(',',$this->getUserProvidersByUserId());
    
    if ($absUrl == ''){
      $absUrl = 'http://' . $_SERVER['HTTP_HOST'] . Zend_Controller_Front::getInstance()->getRequest()->getRequestUri().'?provider=';
    } else{
      $absUrl = $absUrl.'?provider=';
    }
    
    $authUrl = array();

    if (!in_array('google', $user_providers))
      $authUrl['google'] = TBS\Auth\Adapter\Google::getAuthorizationUrl($absUrl.'google');
    if (!in_array('facebook', $user_providers))
      $authUrl['facebook'] = TBS\Auth\Adapter\Facebook::getAuthorizationUrl($absUrl.'facebook');
    if (!in_array('twitter', $user_providers))
      $authUrl['twitter'] = \TBS\Auth\Adapter\Twitter::getAuthorizationUrl($absUrl.'twitter');
    
    return $authUrl;
  }


  public function addProviderAccount($provider, $identifier){
    $identity=Zend_Auth::getInstance()->getIdentity();
    $data = array(
        'user_id' => $identity->item_id,
        'provider' => $provider,
        'identifier' => $identifier,
        'create_time'=>date('Y-m-d H:i:s')
    );
    return $this->db->insert('user_account',$data);
  }

  public function removeProviderAccount($provider, $identifier){
    $identity=Zend_Auth::getInstance()->getIdentity();
    $where = array(
        'user_id = ?' => $identity->item_id,
        'provider  = ?' => $provider,
        'identifier  = ?' => $identifier
    );
    
    $serviceAuth = new Service_Auth();
    $auth = TBS\Auth::getInstance();
    $auth->clearIdentity($provider);
    
    return $this->db->delete('user_account',$where);
  }
    
}