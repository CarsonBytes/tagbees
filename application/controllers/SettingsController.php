<?php
class SettingsController extends Zend_Controller_Action {

  public function preDispatch() {
    if (!Zend_Auth::getInstance() -> hasIdentity()) {
      $this -> _redirect('/auth/login?redirect=' . urlencode($this -> getRequest() -> REQUEST_URI));
      return false;
    }
  }

  public function init() {
    $this -> view -> headTitle('Settings - Tagbees');
  }

  public function indexAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Settings' => null);

    $authService = new Service_Auth();
    // redirection from clicking 1 of the add provider link
    if ( $this->getRequest()->isGet() && $this->_hasParam('provider')) {
        
        $auth = TBS\Auth::getInstance();
        
        $_SESSION['add_login_provider_name'] = $this->_getParam('provider');

        switch ($_SESSION['add_login_provider_name']) {
            case "facebook":
                if ($this->_hasParam('code')) {
                    $adapter = new TBS\Auth\Adapter\Facebook(
                        $this->_getParam('code'), 
                        $this->view->serverUrl() . $this->view->baseUrl('settings?provider=facebook')
                    );
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
                        $this->_getParam('code'), 
                        $this->view->serverUrl() . $this->view->baseUrl('settings?provider=google')
                    );
                    $result = $auth->authenticate($adapter);
                }
                break;

        }
        
        // What to do when invalid
        if (!isset($result)){
            $this->view->errorMessage="no such provider!";
        }else if (!$result->isValid()) {
            $auth->clearIdentity($_SESSION['add_login_provider_name']);
              $this->_helper->FlashMessenger(array('error'=>$_SESSION['add_login_provider_name']." login failed! Please try other method !"));
            //throw new Exception('Error!!'); // should return to failed page with error
        } else {
            //get user identity
            $auth = TBS\Auth::getInstance();
            $providers = $auth->getIdentity();
            
            $identifier = '';
            foreach ($providers as $provider){
                if ($_SESSION['add_login_provider_name'] == $provider->getName()){
                    $identifier = $provider->getId();
                    break;
                }
            }
            
            // check if others have link their account using duplicate credential
            
            $userService = new Service_User();            
            $username = $userService->getUsernameByProvider($_SESSION['add_login_provider_name'], $identifier);
            
            echo '<pre>';var_dump($username);echo '</pre>';
            if ($username == false){
                // no one is using this account credential so the provider should be added
                $authService->addProviderAccount($_SESSION['add_login_provider_name'], $identifier);
              $this->_helper->FlashMessenger(array('success'=>"The account is successfully associated!"));
            }else{
              $this->_helper->FlashMessenger(array('error'=>"Someone is already using this credential in another account!"));
            }
        }
        $this->_redirect('settings');
    } else if ($this->getRequest()->isPost()){
      $settingService = new Service_Setting();
      $settingService->save($this->_request->getPost());
    }
    $this->view->all_providers = $authService->getAllProviders();
    $this->view->authUrl = $authService->getAllUserProviderLinks($this->view->baseUrl('settings'));

    $settingService = new Service_Setting();
    $this -> view -> form_data = $settingService->get();
    
    // for global map js
    Common::getSession()->settings_form->place_lat = $this->view->form_data['place_lat'];
    Common::getSession()->settings_form->place_lng = $this->view->form_data['place_lng'];
  }

}
