<?php
Use Facebook\Facebook;

class Service_Provider{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	  $this->db = Zend_Db_Table::getDefaultAdapter();
	}
  
  private function getGoogleClient($relative_redirect = ''){
    if ($relative_redirect==''){
      $redirect_uri = Common::getSession()->currentUrl;
    } else {
      $redirect_uri = Common::getSession()->baseAbsUrl. $relative_redirect;
    }
    $client = new Google_Client();
    $client->setClientId(Zend_Registry::get('config_ini')->google->client_id);
    $client->setClientSecret(Zend_Registry::get('config_ini')->google->client_secret);
    $client->setRedirectUri($redirect_uri);
    $client->setAccessType('offline');
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
    $client->addScope("https://www.googleapis.com/auth/userinfo.email");
    return $client;
  }
  
  public function getGoogleAuthUrl($relative_redirect = ''){
    return $this->getGoogleClient($relative_redirect)->createAuthUrl();
  }
  
  public function getGoogleProfileInfo($relative_redirect = '', $is_authenticated, $code_or_token){
    if (!$is_authenticated || !isset(Common::getSession('Provider_Google')->access_token)){
      $client=$this->getGoogleClient($relative_redirect);
      
      //then this is the code to get token..
      $client->authenticate($code_or_token);
      
      Common::getSession('Provider_Google')->access_token = 
      json_decode($client->getAccessToken())->access_token;
    }
    $json = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.Common::getSession('Provider_Google')->access_token );
    
    return json_decode($json);
  }
  
  
  private function getFacebookClient(){
    return new Facebook_Facebook(array(
      'appId'  => Zend_Registry::get('config_ini')->facebook->app_id,
      'secret' => Zend_Registry::get('config_ini')->facebook->secret,
    ));
  }
  
  public function getFacebookAuthUrl($relative_redirect=''){
    if ($relative_redirect==''){
      $redirect_uri = Common::getSession()->currentUrl;
    } else {
      $redirect_uri = Common::getSession()->baseAbsUrl. $relative_redirect;
    }
    return $this->getFacebookClient()->getLoginUrl(
      array('redirect_uri'=>$redirect_uri)
    );
  }
  
  public function getFacebookProfileInfo($code){
    $client = $this->getFacebookClient();
    //$token = $client->getAccessToken();
    //echo '<pre>';var_dump($token);echo '</pre>';die();
    //$client->setAccessToken($token);
    
    $user_profile = null;
    try {
      $user_profile = $client->api('/me');
    } catch (FacebookApiException $e) {
      echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
      $user = null;
    }
    return $user_profile;
  }

  public function getUserProvidersByUserId($user_id = ''){
    $identity=Zend_Auth::getInstance()->getIdentity();
    $select = 
      $this->db->select()
        ->from('user_account',array('provider'))
        ->where('user_id = ?',is_int($user_id) ? $user_id : $identity->item_id);
      return $this->db->fetchCol($select);
  }
  
  public function addProviderAccount($provider, $id){
    // check if others have link their account using duplicate credential
    
    $userService = new Service_User();            
    $username = $userService->getUsernameByProvider($provider, $id);
    
    if ($username == false){
      // no one is using this account credential so the provider should be added
      $identity=Zend_Auth::getInstance()->getIdentity();
      $data = array(
          'user_id' => $identity->item_id,
          'provider' => $provider,
          'identifier' => $id,
          'create_time'=>date('Y-m-d H:i:s')
      );
      $this->db->insert('user_account',$data);
        
      return true;
    }else{
      return false;
    }
  }

  public function removeProviderAccount($provider, $user_id=''){
    $identity=Zend_Auth::getInstance()->getIdentity();
    $where = array(
        'user_id = ?' => $user_id=='' ? $identity->item_id : '',
        'provider  = ?' => $provider
    );
    
    $serviceAuth = new Service_Auth();
    $auth = TBS\Auth::getInstance();
    $auth->clearIdentity($provider);
    
    return $this->db->delete('user_account',$where);
  }
}