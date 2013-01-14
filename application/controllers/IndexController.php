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
            //'Latest, 3km from Hong Kong, within 60 days' => null
        );
        
        if (Zend_Auth::getInstance()->hasIdentity()){
            $locationService=new Service_Location();
            Common::getSession()->user_saved_location=$locationService->getSavedLocations();
        }
        
        $feedService=new Service_Feed();
        $feed=$feedService->getFeed();
        $this->view->feed = json_encode($feed);
        
    }
}
