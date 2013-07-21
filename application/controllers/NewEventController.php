<?php
class NewEventController extends Zend_Controller_Action {

  public function preDispatch() {
    if (!Zend_Auth::getInstance() -> hasIdentity()) {
      $this -> _redirect('/auth/login?redirect=' . urlencode($this -> getRequest() -> REQUEST_URI));
      return false;
    }
  }

  public function init() {
    $this -> view -> headTitle('Tagbees - Create Event');
    Common::initTimezoneSession();
    Common::initTreeSession();
  }

  public function indexAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Create Event' => null);
    
    if( $this->getRequest()->isPost() ){
      
      $data = $this->_request->getParams();
      echo '<pre>';var_dump($data);echo '</pre>';die();
      $validators = array(
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
      
      $postService=new Service_Post();
      $slug_name=$postService->add($values);
      
      $this->_helper->FlashMessenger(array('success'=>'Your event \"'.$_POST['name'].'\" has been created.'));
      
      $this->_redirect('/event/' . urlencode($slug_name));
    }
  }

  public function advancedAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Create Event (Advanced)' => null);

  }

}
