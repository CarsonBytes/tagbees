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
            echo $_SESSION['add_login_provider_name']." login failed! Please try other method !";
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
            $username = $userService->getUsernameByProvider($authService->getProviderIdByName($_SESSION['add_login_provider_name']), $identifier);
            
            if ($username == false){
                // no one is using this account credential so the provider should be added
                $authService->addProviderAccount($authService->getProviderIdByName($_SESSION['add_login_provider_name']), $identifier);
                echo "The account is successfully associated!";
            }else{
                echo "Someone is already using this credential in another account!";
            }
        }
        $this->_redirect('settings');
    } else if ($this->getRequest()->isPost()){
      $settingService = new Service_Setting();
      $settingService->save($this->_request->getPost());
    }
    $this->view->all_account_providers = $authService->getAllProviders();
    $this->view->authUrl = $authService->getAllProviderLinks();

    $settingService = new Service_Setting();
    $this -> view -> form_data = $settingService->get();
    //echo '<pre>';var_dump($this->view->form_data);echo '</pre>';die();

    // for not commly changed data we store them in session...
    if (!isset($_SESSION['timezone'])) {
      $timezoneService = new Service_Timezone();
      $_SESSION['timezone'] = $timezoneService -> get();
    }
    if (!isset($_SESSION['timezone_json'])) {
      $js = array();
      foreach ($_SESSION['timezone'] as $timezone) {
        if (!in_array($timezone['country_code'], $js)) {
          $js[] = $timezone['country_code'];
          $js[] = array($timezone['timezone_id'], $timezone['city'] . ' (' . $timezoneService -> formatOffset($timezone['offset']) . ')');
        } else {
          $key = array_search($timezone['country_code'], $js);
          $js[$key + 1][] = $timezone['timezone_id'];
          $js[$key + 1][] = $timezone['city'] . ' (' . $timezoneService -> formatOffset($timezone['offset']) . ')';
        }
      }
      $_SESSION['timezone_json'] = json_encode($js);
    }
  }

}
