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
            if ($action == 'reset_password'){
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
                $mailService->setTemplate('user_signup_welcome');
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
            die();
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
              
            }else if (!isset(Common::getSession()->user_signup_confirmation->email_template_vars)){
              // no email template vars is generated during user creation for some reasons
              $this->_helper->FlashMessenger(array('error'=>'Failed to add the account. Please try again later.'));
              $this->_redirect('');
            }
        } 
        /*
         * up to here the $_COOKIE['to_confirm_email'] can only be valid, 
           $_COOKIE['is_email_confirmed'] is 0, 
         * and Common::getSession()->user_signup_confirmation->email_template_vars was set
         * So we are sure the user was redirected right after account creation
        */
        $this->view->to_confirm_email = $_COOKIE['to_confirm_email'];
        
        $mailService = new Service_Mail();
        $mailService->setTemplate('user_signup_confirmation');
        $mailService->setRecipientEmail(Common::getSession()->user_signup_confirmation->email_template_vars->email);
        $mailService->setRecipientName(Common::getSession()->user_signup_confirmation->email_template_vars->display_name);
        $mailService->setTemplateVars(Common::getSession()->user_signup_confirmation->email_template_vars);
        $mailService->send();
        
        // delete all possible credentials like previous oauth provider connections
        $this->signUserOut();
        unset(Common::getSession()->user_signup_confirmation->email_template_vars);
        unset($_COOKIE['user_signup']);
        unset($_COOKIE['is_email_confirmed']);
        unset($_COOKIE['to_confirm_email']);
    }
    public function signupAction(){
        
        Common::getSession()->nav=array(
            'Home' => '/',
            'Signup' => null
        );
        
        $auth = TBS\Auth::getInstance();

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
                
                $user = new Service_User();
                 $this->view->result = $user->add(
                     $input->username,
                     $input->password,
                     $input->email,
                     $input->gender,
                    $input->display_name,
                    (isset(Common::getSession()->display_lang)) ? Common::getSession()->display_lang : 'zh-hk',
                    (isset(Common::getSession()->user_signup->provider)) ? Common::getSession()->user_signup->provider : null,
                     (isset($data['relateds'])) ? $data['relateds'] : array(),
                    (isset($data['related_types'])) ? $data['related_types'] : array()
                 );
                Common::setCookie('to_confirm_email', $input->email);
                Common::setCookie('is_email_confirmed', 0);
                $this->_redirect('auth/signup/confirm');
            }
        }

        if ($auth->hasIdentity()) {
            $providers = $auth->getIdentity();
            foreach ($providers as $provider){
                // it is supposed to be logined with only 1 provider
                if (isset(Common::getSession()->login_provider_name) && Common::getSession()->login_provider_name == $provider->getName()){
                    // create new account with prefilled data from providers
                    Common::setCookie("user_signup", json_encode($this->getSignupFormCookieFromProvider($provider)));
                }
            }
        }
    }

    public function loginAction(){
        Common::getSession()->nav=array(
            'Home' => '/',
            'Login' => null
        );
        
        // redirection from clicking 1 of the providers button
        if ( $this->getRequest()->isGet() && $this->_hasParam('provider')) {
            
            $auth = TBS\Auth::getInstance();
            
            Common::getSession()->login_provider_name = $this->_getParam('provider');

            switch (Common::getSession()->login_provider_name) {
                case "facebook":
                    if ($this->_hasParam('code')) {
                        $adapter = new TBS\Auth\Adapter\Facebook(
                                $this->_getParam('code'));
                        $result = $auth->authenticate($adapter);
                    }
                    break;
                case "twitter":
                    if ($this->_hasParam('oauth_token')) {
                        $adapter = new TBS\Auth\Adapter\Twitter($_GET);
                        $result = $auth->authenticate($adapter);
                    }
                    break;
                case "google":
                    if ($this->_hasParam('code')) {
                        $adapter = new TBS\Auth\Adapter\Google(
                                $this->_getParam('code'));
                        $result = $auth->authenticate($adapter);
                    }
                    break;

            }
            
            // What to do when invalid
            if (!isset($result)){
                $this->view->errorMessage="no such provider!";
            }else if (!$result->isValid()) {
                $auth->clearIdentity(Common::getSession()->login_provider_name);
                $this->view->errorMessage=Common::getSession()->login_provider_name." login failed! Please try other method !";
                //throw new Exception('Error!!'); // should return to failed page with error
            } else {
                $auth = TBS\Auth::getInstance();
                $providers = $auth->getIdentity();
                $identifier = '';
                foreach ($providers as $provider){
                    if (Common::getSession()->login_provider_name == $provider->getName()){
                        $identifier = $provider->getId();
                        break;
                    }
                }
                
                $serviceAuth = new Service_Auth();
                
                $userService = new Service_User ();
                
                $username = $userService->getUsernameByProvider(Common::getSession()->login_provider_name, $identifier);
                if ($username == false){
                   // direct user to create a new account
                   $this->_redirect('auth/signup');
                }else{
                   $this->signUserIn($username);
                }
            }

        //login form submission..
        } else if( $this->getRequest()->isPost() ) {
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
                $this->view->errorMessage="Username or password is not correct!";
            }
        }
        
        // Normal login page
        $this->view->googleAuthUrl = urldecode(TBS\Auth\Adapter\Google::getAuthorizationUrl());
        $this->view->facebookAuthUrl = urldecode(TBS\Auth\Adapter\Facebook::getAuthorizationUrl());
        $this->view->twitterAuthUrl = urldecode(TBS\Auth\Adapter\Twitter::getAuthorizationUrl());
        
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
            $email = $this->_request->getParam('email');
            
            $userService = new Service_User();
            $user = $userService->getUserByEmail($email);
            if ($user != NULL && $user->is_confirmed == 1){
                $template_vars->display_name = $user->display_name;
                $template_vars->email = $email;
                $template_vars->code = Common::nonce();
                
                $userService->setResetPasswordCode($template_vars->code, $user->item_id);
                
                $mailService = new Service_Mail();
                $mailService->setTemplate('user_to_reset_password');
                $mailService->setRecipientEmail($email);
                $mailService->setRecipientName($user->display_name);
                $mailService->setTemplateVars($template_vars);
                $mailService->send();
                
            }else{
                // sleep for 3 seconds to fake the waiting time for sending email
                sleep(3);
            }
            
            $this->_helper->FlashMessenger(array('success'=>'The reset password link has been sent to this email address: '. $email .'. Please check the mailbox.'));
            $this->_redirect('auth/forgot_password');
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
    private function getSignupFormCookieFromProvider($provider){
        //$cookie is the signup form values to be sent to users as prefilled form data
        $cookie = array();
        $profile = $provider->getApi()->getProfile(); 
        $provider_name = $provider->getName();
        
        /*
         * Common::getSession()->user_signup is other datas (e.g. display_lang)
         * to be stored in db after the account is created successfully
         */
        Common::getSession()->user_signup->provider = $provider;
        
        if ($provider_name == 'google'){
            $cookie['email'] = isset ($profile['email']) ? $profile['email'] : '';
            $cookie['gender'] = (isset ($profile['gender']) && $profile['gender'] == 'female') ? 'f' : 'm';
            $cookie['display_name'] = isset ($profile['name']) ? str_replace('+', ' ', $profile['name']) : '';
            
            if (isset ($profile['locale']))
                Common::getSession()->display_lang = $profile['locale'];
                        
        }else if ($provider_name == 'twitter'){
            $cookie['display_name'] = isset ($profile['name']) ? str_replace('+', ' ', $profile['name']) : '';
            $cookie['username'] = isset ($profile['screen_name']) ? $profile['screen_name'] : '';
            
            if (isset ($profile['lang']))
                Common::getSession()->display_lang = $profile['lang'];
            
        }else if ($provider_name == 'facebook'){
            $cookie['display_name'] = isset ($profile['name']) ? str_replace('+', ' ', $profile['name']) : '';
            $cookie['username'] = isset ($profile['screen_name']) ? $profile['screen_name'] : '';
            $cookie['email'] = isset ($profile['email']) ? $profile['email'] : '';
            $cookie['gender'] = (isset ($profile['gender']) && $profile['gender'] == 'female') ? 'f' : 'm';
            
            if (isset ($profile['locale']))
                Common::getSession()->display_lang = $profile['locale'];
        }
        return $cookie;
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
            Zend_Session::destroy();
            //unset(Common::getSession()->user);
        }
    }
}
