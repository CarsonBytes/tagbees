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

    $providerService = new Service_Provider();
    // redirection from clicking 1 of the add provider link
    if ( $this->getRequest()->isGet() && $this->_hasParam('provider')) {

        switch ($this->_getParam('provider')) {
            case "facebook":
                if ($this->_hasParam('code')) {
                  $result = $providerService->getFacebookProfileInfo($this->_getParam('code'));
                  $id=$result['id'];
                }
                break;
            case "google":
                if ($this->_hasParam('code')) {
                  $id = $providerService->getGoogleProfileInfo(
                    'settings?provider=google',$this->_getParam('code')
                    )->id;
                }
                break;
        }
        $result = $providerService->addProviderAccount($this->_getParam('provider'), $id);
        if ($result){
          $this->_helper->FlashMessenger(array('success'=>"The account is successfully associated!"));
        } else {
          $this->_helper->FlashMessenger(array('error'=>"Someone is already using this credential in another account!"));
        }
        $this->_redirect('settings');
    } else if ($this->getRequest()->isPost()){
      $settingService = new Service_Setting();
      $settingService->save($this->_request->getPost());
    }
    
    $user_provider_names = $providerService->getUserProvidersByUserId();
    $this->view->auth_link = array();
    $this->view->user_name = array();
    $this->view->user_pic = array();
    if (!in_array('google', $user_provider_names)){
      $this->view->auth_link['google'] = $providerService->getGoogleAuthUrl('settings?provider=google');
    }
    
    if (!in_array('facebook', $user_provider_names)){
      $this->view->auth_link['facebook'] = $providerService->getFacebookAuthUrl('settings?provider=facebook');
    }
    
    $settingService = new Service_Setting();
    $this->view->form_data = $settingService->get();
    
    // for global map js
    Common::getSession()->settings_form->place_lat = $this->view->form_data['place_lat'];
    Common::getSession()->settings_form->place_lng = $this->view->form_data['place_lng'];
  }

}
