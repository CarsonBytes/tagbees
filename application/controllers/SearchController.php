<?php
class SearchController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->headTitle('Tagbees');
    }
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Search' => null,
            //'Latest, 3km from Hong Kong, within 60 days' => null
        );
        
        if (Zend_Auth::getInstance()->hasIdentity()){
            $locationService=new Service_UserSavedLocation();
            Common::getSession()->user_saved_locations=$locationService->getSavedLocations();
        }
        
        /*$feedService=new Service_Feed();
        $this->view->feed = $feedService->getFeed($this->getRequest()->getParams());*/
    }
}
