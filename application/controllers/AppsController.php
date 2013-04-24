<?php
class AppsController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->headTitle('Tagbees');
    }
    
    public function indexAction()
    {
      Common::getSession()->nav=array(
          'Home' => '/',
          'Apps' => null
      );
    }
}
