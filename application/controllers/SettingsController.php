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

    $settingService = new Service_Setting();
    $this->view->form_data = $settingService->get();
    
    // for not commly changed data we store them in session...
    if (!isset($_SESSION['timezone'])) {
      $timezoneService = new Service_Timezone();
      $_SESSION['timezone'] = $timezoneService->get();
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
