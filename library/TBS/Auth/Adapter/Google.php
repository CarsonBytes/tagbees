<?php
namespace TBS\Auth\Adapter;

use \TBS\Auth\Identity\Google as Identity;
use \TBS\OAuth2\Consumer;

use \Zend_Auth_Result as Result;
use \Zend_Registry as Registry;

class Google implements \Zend_Auth_Adapter_Interface
{
   protected $_accessToken;
   protected $_requestToken;
   protected $_options;
 
   public function __construct($requestToken, $redirect_uri = '')
   {
      $options = Registry::get('config_ini');
      if ($redirect_uri == '') {
          $redirect_uri = $options->google->redirect_uri;
      }
      $this->_setOptions($redirect_uri);
      $this->_setRequestToken($requestToken);
   }
 
   public function authenticate()
   {
      $result = array();
      $result['code'] = Result::FAILURE;
      $result['identity'] = NULL;
      $result['messages'] = array();
 
      if(!array_key_exists('error',$this->_accessToken)) {
         $result['code'] = Result::SUCCESS;
         $result['identity'] = new Identity($this->_accessToken);
      }
 
      return new Result($result['code'],
                                  $result['identity'],
                                  $result['messages']);
   }
 
   public static function getAuthorizationUrl($redirect_uri = '')
   {
      $options = Registry::get('config_ini');
      if ($redirect_uri != '') {
          $options->google->redirect_uri = $redirect_uri;
      }
      return Consumer::getAuthorizationUrl($options->google);
   }
 
 
   protected function _setRequestToken($requestToken)
   {
      $this->_options->code = $requestToken;
      
      $accesstoken = Consumer::getAccessToken($this->_options);

      $accesstoken['timestamp'] = time();
      $this->_accessToken = $accesstoken;
   }
 
   protected function _setOptions($redirect_uri = null)
   {
      $options = Registry::get('config_ini');
      if ($redirect_uri != '') {
          $options->google->redirect_uri = $redirect_uri;
      }
      $this->_options = $options->google;
   }
}
