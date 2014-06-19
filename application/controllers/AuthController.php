<?php
class AuthController extends Zend_Controller_Action
{
  private $session_table_config=array(
      'name'=>'sessions',
      'primary' => array(
          'session_id',   //the sessionID given by PHP
          'save_path',    //session.save_path
          'name',         //session name
      ),
      'primaryAssignment' => array(
          'sessionId', 'savePath', 'name',
      ),
      /*'user_idColumn'    => 'id',
      'create_timeColumn' =>'create_time',
      'ipColumn' =>'ip',
      'http_user_agentColumn' =>'http_user_agent',*/
      'modifiedColumn'    => 'modified',
      'dataColumn'        => 'session_data',
      'lifetimeColumn'    => 'lifetime',
  );
  public function preDispatch()
  {
      if (!$this->_request->isXmlHttpRequest()){
          $this->_helper->_layout->setLayout('1-col-left');
      }else{
          $this->_helper->layout->disableLayout();
      }
      
      $action = $this->getRequest()->getActionName();
      
      if (Zend_Auth::getInstance()->hasIdentity()){
          if ($action == 'reset_password' || 
              $action == 'forgot_password'){
              $this->signUserOut();
          } else if ($action != 'logout'){
              $this->_redirect('');
          }
          
      }
      if ($action == 'signup' || $action == 'login' || 
          $action == 'forgot_password' || $action == 'signup_confirm') {
      }else if ($action == 'reset_password'){
          $this->signUserOut();
      }
  }
  
  public function init()
  {
      $this->view->headTitle('Account - Tagbees');
  }
  
  public function signupConfirmAction(){
      $params=$this->_request->getParams();
      if (isset($params['code']) && $params['code']!=''){
          $userService = new Service_User();
          $user = $userService->getUserByConfirmCode($params['code']);
          
          if ($user->is_confirmed != 0 && $user->is_confirmed != 1) $this->_redirect('');
          
          if ($user->is_confirmed == 0 ) {
              $userService->setIsConfirmedByConfirmCode($params['code'], 1);
              
              $mailService = new Service_Mail();
              $mailService->setTemplate('auth_signup_welcome');
              $mailService->setRecipientEmail($user->email);
              $mailService->setRecipientName($user->name);
              $mailService->setTemplateVars($user);
              $mailService->send();
              
              $this->signUserOut();
              unset($_COOKIE['is_email_confirmed']);
              unset($_COOKIE['to_confirm_email']);
              
              $this->_helper->FlashMessenger(array('success'=>'Welcome! you have just confirmed your account! Please sign in.'));
          }
          
          $this->_redirect('auth/login');
          
      }else if (!isset($_COOKIE['to_confirm_email']) || !isset($_COOKIE['is_email_confirmed'])){
          $this->_redirect('');
      }else{
          $validator = new Zend_Validate_EmailAddress();
          if ($validator->isValid($_COOKIE['to_confirm_email']) && $_COOKIE['is_email_confirmed'] == 1) { 
            // email is confirmed  
            $this->_helper->FlashMessenger(array('notice'=>'This email is already confirmed. You may login.'));
            $this->_redirect('auth/login');
          
          } else if ($validator->isValid($_COOKIE['to_confirm_email']) == false){
            // email is not valid
            $this->_helper->FlashMessenger(array('error'=>'Email is invalid. Please try again later.'));
            $this->_redirect('');
            
          }else if (!isset(Common::getSession()->auth_signup_confirmation->email_template_vars)){
            // no email template vars is generated during user creation for some reasons
            $this->_helper->FlashMessenger(array('error'=>'Failed to add the account. Please try again later.'));
            $this->_redirect('');
          }
      } 
      /*
       * up to here the $_COOKIE['to_confirm_email'] can only be valid, 
         $_COOKIE['is_email_confirmed'] is 0, 
       * and Common::getSession()->auth_signup_confirmation->email_template_vars was set
       * So we are sure the user was redirected right after account creation
      */
      $this->view->to_confirm_email = $_COOKIE['to_confirm_email'];
      
      $mailService = new Service_Mail();
      $mailService->setTemplate('auth_signup_confirmation');
      $mailService->setRecipientEmail(Common::getSession()->auth_signup_confirmation->email_template_vars->email);
      $mailService->setRecipientName(Common::getSession()->auth_signup_confirmation->email_template_vars->display_name);
      $mailService->setTemplateVars(Common::getSession()->auth_signup_confirmation->email_template_vars);
      $mailService->send();
      
      // delete all possible credentials like previous oauth provider connections
      $this->signUserOut();
      //unset(Common::getSession()->auth_signup_confirmation->email_template_vars);
      unset($_COOKIE['user_signup']);
      unset($_COOKIE['is_email_confirmed']);
      unset($_COOKIE['to_confirm_email']);
  }
  public function signupAction(){
      
    Common::getSession()->nav=array(
        'Home' => '/',
        'Signup' => null
    );
    $providerService = new Service_Provider();

    $this->view->auth_link=new stdClass();
    $this->view->auth_link->google = $providerService->getGoogleAuthUrl('auth/login?provider=google');
  
    $this->view->auth_link->facebook = $providerService->getFacebookAuthUrl('auth/login?provider=facebook');
        
    if( $this->getRequest()->isPost() ){

        $data = $this->_request->getParams();

        $filters=array(
            //'username'   => 'StringTrim'
        );
        $validators = array(
            'username'   => array(
                new Zend_Validate_Db_NoRecordExists(
                    array(
                        'table' => 'user',
                        'field' => 'username'
                    )
                )
                ,new Validate_Username(),
                /*,
                new Zend_Validate_Alnum(
                    array('allowWhiteSpace' => false)
                ),*/
                //'messages' => array(1=>"the username contains characters which are non alphabetic and no digits")
            ),
            'password'   => array(
                new Zend_Validate_StringLength(
                    array('min' => 6)
                ),
                'messages' => 'the password should contain at least 6 characters'
            ),
            'email'   => array(
                new Zend_Validate_EmailAddress(
                ),
                'messages' => 'the email is not valid'
            ),
            'gender'   => array(
            ),
            'display_name'   => array(
            )/*,
            '*'   => array('allowEmpty'=>false)*/
        )
        ;
        $options= array(
            'missingMessage' => "'%field%' is required"
        );
        $input = new Zend_Filter_Input($filters, $validators,$data,$options);


        if ($input->hasInvalid() || $input->hasMissing()) {
          $this->view->result=$input->getMessages();
        }else{
          $provider_name = $data['provider_name'];
          $has_provider_info = false;
          if (isset(Common::getSession('Providers')->$provider_name->info)){
            $provider_info = Common::getSession('Providers')->$provider_name->info;
            $provider_info->provider_name = $provider_name;
            //$provider_info->is_email_verified = $this->isProviderEmailVerified($provider_info, $provider_name);
            $has_provider_info = true;
          }
            $user = new Service_User();
             $this->view->result = $user->add(
               $input->username,
               $input->password,
               $input->email,
               $input->gender,
               $input->display_name,
               (isset(Common::getSession()->display_lang)) ? Common::getSession()->display_lang : 'zh-HK',
               ($has_provider_info) ? $provider_info : '',
               (isset($data['relateds'])) ? $data['relateds'] : array(),
               (isset($data['related_types'])) ? $data['related_types'] : array()
             );
            /*if ($has_provider_info){
              unset($_COOKIE['is_email_confirmed']);
              unset($_COOKIE['to_confirm_email']);
              
              $this->_helper->FlashMessenger(array('success'=>'Welcome! you have created your account! Please sign in.'));
              $this->_redirect('auth/login');
            }*/
            Common::setCookie('to_confirm_email', $input->email);
            Common::setCookie('is_email_confirmed', 0);
            
            //let them play around first
            $this->signUserIn($input->username);
            $this->_redirect('');
        }
    }
    
    $this->view->has_provider = false;
    //$this->view->is_email_verified = false;
    if ($this->_hasParam('no_provider')){
      $this->_redirect('auth/signup');
    } else if ($this->_hasParam('provider')){
      $this->view->provider_name = $this->_getParam('provider');
      $session_provider = $providerService->getSessionProvider($this->view->provider_name);
      if (isset($session_provider->info)){
        $this->view->has_provider = true;
        $session_provider_info = $session_provider->info;
        //if ($this->view->provider_name == 'google'){
            $this->view->email = isset ($session_provider_info->email) ? $session_provider_info->email : '';
            //$this->view->is_email_verified = $this->isProviderEmailVerified($session_provider_info, 'google');
            $this->view->gender = (isset ($session_provider_info->gender) && $session_provider_info->gender == 'female') ? 'F' : 'M';
            $this->view->name = isset ($session_provider_info->name) ? $session_provider_info->name : '';
            
            if (isset ($session_provider_info->locale))
             Common::getSession()->display_lang = $session_provider_info->locale;
                                      
        /*}else if ($this->view->provider_name == 'facebook'){
            $this->view->email = isset ($session_provider_info->email) ? $session_provider_info->email : '';
          $this->view->gender = (isset ($session_provider_info->gender) && $session_provider_info->gender == 'female') ? 'F' : 'M';
            $this->view->name = isset ($session_provider_info->name) ? $session_provider_info->name : '';
            
            if (isset ($session_provider_info->locale))
             Common::getSession()->display_lang = $session_provider_info->locale;
        }*/
      }
    }
  }

  public function loginAction(){
      Common::getSession()->nav=array(
          'Home' => '/',
          'Login' => null
      );
      
      $providerService = new Service_Provider();
      
      $this->view->auth_link=new stdClass();
      $this->view->auth_link->google = $providerService->getGoogleAuthUrl();
    
      $this->view->auth_link->facebook = $providerService->getFacebookAuthUrl();
      
      // redirection from clicking 1 of the add provider link
      if ( $this->getRequest()->isGet() && $this->_hasParam('provider')) {
          $provider_name = $this->_getParam('provider');
          switch ($this->_getParam('provider')) {
              case "facebook":
                  if ($this->_hasParam('code')) {
                    $result = $providerService->getFacebookProfileInfo($this->_getParam('code'));
                    $id=$result['id'];
                  }
                  break;
              case "google":
                  if ($this->_hasParam('code')) {
                    $id = $providerService->getGoogleProfileInfo('', false, $this->_getParam('code'))->id;
                  }
                  break;
          }
          if ($id!=null){
            $username = $providerService->getUsernameByProvider($this->_getParam('provider'), $id);
            //echo '<pre>';var_dump($username);echo '</pre>';die();
            if ($username == null){
              // redirect user to create new account
              $this->_redirect('auth/signup?provider='.$provider_name);
            }else{
               $this->signUserIn($username);
            }
          }else{
              $this->_helper->FlashMessenger(array('error'=>"Cannot login! Please try again later!"));
              $this->_redirect('auth/login');
          }
      } else if( $this->getRequest()->isPost() ) {
        //login form submission..
          $data=$this->getRequest()->getPost();
  
          $authAdapter= new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
          $authAdapter->setTableName('user')
                      ->setIdentityColumn('username')
                      ->setCredentialColumn('password')
                      ->setCredentialTreatment('MD5(?)')
                      ->setIdentity( $data['username'] )
                      ->setCredential( $data['password']);
  
          $objAuth = Zend_Auth::getInstance();
          $result = $objAuth->authenticate($authAdapter);
  
          if ( $result->isValid() ) {
              $this->signUserIn($data['username'], $objAuth);
          }else {
              //$this->view->errorMessage="Username or password is not correct!";
            $this->_helper->FlashMessenger(array('error'=>'Username or password is not correct!'));
            $this->_redirect('auth/login');
          }
      }
      
  }

  public function logoutAction()
  {
      $this->signUserOut();
      $this->_redirect('');
  }
  public function forgotPasswordAction(){
      Common::getSession()->nav=array(
          'Home' => '/',
          'Forgot Password' => null
      );
      
      if( $this->getRequest()->isPost() ){
          $email = $this->_request->getParam('forgot_password_email');
          
          $userService = new Service_User();
          $user = $userService->getUserByEmail($email);
          if ($user != NULL && $user->is_confirmed == 1){
              $template_vars->display_name = $user->display_name;
              $template_vars->email = $email;
              $template_vars->code = Common::nonce();
              
              $userService->setResetPasswordCode($template_vars->code, $user->item_id);
              
              $mailService = new Service_Mail();
              $mailService->setTemplate('auth_to_reset_password');
              $mailService->setRecipientEmail($email);
              $mailService->setRecipientName($user->display_name);
              $mailService->setTemplateVars($template_vars);
              $mailService->send();
              
          }else{
              // sleep for 3 seconds to fake the waiting time for sending new email to unregistered users
              sleep(3);
          }
          
          $this->_helper->FlashMessenger(array('success'=>'The reset password link has been sent to this email address: '. $email .'. Please check the mailbox.'));
          $this->_redirect('auth/login');
      }
  }
  public function resetPasswordAction(){
      Common::getSession()->nav=array(
          'Home' => '/',
          'Reset Password' => null
      );
      
      $userService = new Service_User();
      $params = $this->getRequest()->getParams();
      
      // if reset password form is being submitted
      if( $this->getRequest()->isPost() ){
          
          $result = $userService->updatePasswordByEmail($params['password'], Common::getSession()->user_reset_password_email);
          if ($result == true){
              $userService->disableResetPasswordToken(Common::getSession()->user_reset_password_email);
              $this->_helper->FlashMessenger(array('success'=>'Your password is reset successfully. You can now login with your new password.'));
              $this->_redirect('auth/login');
          }else{
              $this->_helper->FlashMessenger(array('error'=>'Your password is not reset. Please try again later.'));
              $this->_redirect('');
          }
          
      }
      
      // if url is correctly formed
      if( isset($params['email']) &&  isset($params['code']) ){
          if ($userService->isRestorePasswordCodeValid($params['email'], $params['code'])) {
              // here comes when it is a valid reset password url and not a form submission POST request
              $this->view->email = Common::getSession()->user_reset_password_email = $params['email'];
          }else{
              // Up to here it is not a valid reset password url, then just redirects to main page
              $this->_redirect('');
          }
      }else{
          // Up to here it is not a valid reset password url, then just redirects to main page
          $this->_redirect('');
      }
      
  }

  protected function signUserIn($username, $objAuth = NULL){
      if ($objAuth == NULL) $objAuth = Zend_Auth::getInstance();
      
      $userService = new Service_User();
      $userService->updateLastSeenTime($username);
      
      //session management
      Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($this->session_table_config));
      Zend_Session::start();
      
      $userData = $userService->getUserDataByUsername($username,Zend_Db::FETCH_OBJ);
      $objAuth->getStorage()->write( $userData );
      Common::getSession()->user = $userData;
      
      
      if (Common::getSession()->redirect!='' && isset(Common::getSession()->redirect)){
          $this->_redirect(Common::getSession()->redirect,array('prependBase'=>false));
      }else{
          $this->_redirect('');
      }
  }
  
  protected function signUserOut(){
      if (Zend_Auth::getInstance()->hasIdentity()) {
          Zend_Auth::getInstance()->clearIdentity();
          //unset(Common::getSession()->user);
      Zend_Session::destroy();
      }
  }
  
/*  private function isProviderEmailVerified($provider_info, $provider_name){
    if ($provider_name == 'google' && $provider_info->verified_email != false){
      return true;
    } else if ($provider_name == 'facebook' && isset($provider_info['email'])){
      return true;
    }
    return false;
  }*/
}
