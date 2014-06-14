<?php

class Ajax_AuthController extends Zend_Controller_Action
{
	protected $feedService;
	private $params;
  private $db;
  public function init()
  {
  	if(!$this->getRequest()->isPost()){
  		exit(0);
  	}else{
  		$this->params=$this->_request->getPost();
  	}
    //$this->feedService=new Service_Feed();
    $this->db = Zend_Db_Table::getDefaultAdapter();
  }
    
  public function openLoginBoxAction(){
        $array = array();
        if (Zend_Auth::getInstance()->hasIdentity()){
            $array['result'] = false;
        } else{
            // Normal login page
            $array['result'] = true;
            $array['data'] = array(
                'googleAuthUrl'=> TBS\Auth\Adapter\Google::getAuthorizationUrl(),
                'facebookAuthUrl'=> TBS\Auth\Adapter\Facebook::getAuthorizationUrl(),
                'twitterAuthUrl'=> TBS\Auth\Adapter\Twitter::getAuthorizationUrl()
            );
        }
        
        $this->_helper->json($array);
    }

  public function validateLoginAction()
  {
    $authService = new Service_Auth();
    $msg = $authService->validateLogin($this->params['username'], $this->params['password']);
    if (!$msg){
      $msg = 'Username or Password is false!';
    } else{
      $msg = true;
    }
    $this->_helper->json($msg);
    
  }

  public function usernameValidateAction(){
    $msg=true;
    //validate same username existency
    $select=$this->db->select()
        ->from ('user', array('count(*)'))
        ->where ('username = ?',$this->params['username']);
    if ($this->db->fetchOne($select)){
      $msg='the username is already taken. Please try another one.';
    }
    $this->_helper->json($msg);
  }

    public function userEmailValidateAction(){
        $msg=true;
        //validate same email existency
        $select=$this->db->select()
                ->from ('user', array('count(*)'))
                ->where ('email = ?',$this->params['email']);
        if ($this->db->fetchOne($select)){
            $msg='the email is already taken. Please try another one.';
        }
        $this->_helper->json($msg);
    }

    public function userRecaptchaValidateAction(){
        require_once('recaptchalib.php');
        $msg=true;
        //validate same email existency$privatekey = "your_private_key";
        $resp = recaptcha_check_answer (Zend_Registry::get('config_ini')->recaptcha->privateKey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $this->params["recaptcha_challenge_field"],
                                    $this->params["recaptcha_response_field"]);
        if (!$resp->is_valid) {
            // What happens when the CAPTCHA was entered incorrectly
            $msg = "The reCAPTCHA wasn't entered correctly. Go back and try it again."
             //."(reCAPTCHA said: " . $resp->error . ")"
             ;
        }
        $this->_helper->json($msg);
    }
}

