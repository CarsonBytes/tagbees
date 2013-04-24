<?php
class EventController extends Zend_Controller_Action
{
    
    public function preDispatch(){
       
    }
    
    public function indexAction()
    {
      if ($this->_request->getParam('slug_name')==""){
      }else{
        $itemService=new Service_Item();
        if ($this->_request->getParam('tree_ref') != NULL) {
          Zend_Registry::set('tree_ref', explode(',', $this->_request->getParam('tree_ref')));
        }
        $result=
          $itemService->getItemBySlugName(
              urldecode($this->_request->getParam('slug_name'))
          );
        if ($result){
          $this->view->item=$result['item'];
          $this->view->have_gallery=1;
          $this->view->related_categories=$result['related_categories'];
          $this->view->result=1;
          
          $this->view->headTitle($result['item']->name);
          
          Common::getSession()->nav=array(
              'Home' => '/',
              'Event '.$result['item']->name => null
          );
        }else{
          $this->view->result=-1;
        }
      }
    }
}
