<?php
class EventController extends Zend_Controller_Action
{
    
    public function preDispatch(){
       
    }
    
    public function indexAction()
    {      
      if ($this->_request->getParam('slug_name')==""){
      }else{
        //$itemService=new Service_Item();
        if ($this->_request->getParam('tree_ref') != NULL) {
          Zend_Registry::set('tree_ref', explode(',', $this->_request->getParam('tree_ref')));
        }
        /*$result=
          $itemService->getItemBySlugName(
              urldecode($this->_request->getParam('slug_name'))
          );
        */
                    
        $feedService=new Service_Feed();
        $result = $feedService->getFeed(
          array('select_slug_name'=>
            urldecode($this->_request->getParam('slug_name'))
          )
        );
        
        
        if ($result){
          $this->view->array_result = $result;
          
          //$this->view->item=$result['item'];
          $this->view->item=$result['data'][0];
          //$this->view->have_gallery=1;
          //$this->view->related_categories=$this->view->item->related_categories;
          $this->view->result=1;
          
          $this->view->headTitle($this->view->item->name);
          
          $this->view->item->gallery_url = Common::getSession()->baseUrl . '/iframe/event/img_gallery?event_id='.$this->view->item->id ;
          
          Common::getSession()->nav=array(
              'Home' => '/',
              'Event '.$this->view->item->name => null
          );
          
        }else{
          $this->view->result=-1;
        }
      }
    }
    public function uploadFormAction()
    {
      $this->_helper->layout->setLayout('empty');
      
    }
}
