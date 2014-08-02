<?php
Use Facebook\Facebook;

class Service_Translation{
  protected $db;
  protected $service;
	function __construct(){
    $client = new Google_Client();
    $client->setDeveloperKey(Zend_Registry::get('config_ini')->google->api_key);
    $this->service = new Google_Service_Translate($client);
	}
  
  public function getTranslation($q,$source='',$target='en',$param=array()){
    if ($source == $target) return false;
    
    if ($source != '' ) $param['source'] = $source;
    
    $result = $this->service->translations->listTranslations($q,$target,$param);
    
    if ($result == NULL) return false;
    return $result['data']['translations'][0]['translatedText'];
  }
}