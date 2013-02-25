<?php

class TagController extends Zend_Controller_Action
{
	protected $identity;

	public function preDispatch(){
	}
    public function indexAction()
    {
    	if ($this->_request->getParam('slug_name')==""){
			$this->_redirect('/');
    	}else{
		    $itemService=new Service_Item();
		    $tag=
		    	$itemService->getItemBySlugName(
    				urldecode($this->_request->getParam('slug_name')),
					array('tag','category_tag')
		    	);
				$this->view->tag_item=$tag['item'];
            
            if ($tag == false){
                $this->_redirect('/');
            }
    			
            $this->view->headTitle('Tag '.$tag['item']->name);
            
    		if (is_int($tag['item']->id)){
    			$this->view->result=1;
				
                $feedService=new Service_Feed();
				$feed = $feedService->getFeed(array('tag_id'=>$tag['item']->id));
                $this->view->tag_events = $feedService->getFeed(array('tag_id'=>$tag['item']->id));
                
    		}else{
                $this->_redirect('/');
			}
		}
    }
}

