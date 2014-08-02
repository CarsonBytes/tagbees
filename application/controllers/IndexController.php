<?php
class IndexController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->headTitle('Tagbees');
    }
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => null,
        );
        $this->_helper->layout->setLayout('splash');
        
        // TODO TAG:54 google translation
        //$translateService = new Service_Translation();
        //$result = $translateService->getTranslation('有');
        
        $isLogined = (Zend_Auth::getInstance()->hasIdentity() && Common::getSession()->user != null) ?true:false;
        if ($isLogined){
          $this->_redirect('search');
        }
    }
}
