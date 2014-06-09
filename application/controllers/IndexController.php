<?php
class IndexController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->headTitle('Tagbees');
        $this->_helper->layout->setLayout('splash');
    }
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => null,
        );
        
        if (Zend_Auth::getInstance()->hasIdentity()){
          $this->_redirect('search');
        } else {
          
        }
    }
}
