<?php

class Ajax_FeedController extends Zend_Controller_Action
{
	protected $feedService;
	private $params;
    public function init()
    {
		if(!$this->getRequest()->isPost()){
			exit(0);
		}else{
			$this->params=$this->_request->getPost();
		}
	    //$this->feedService=new Service_Feed();
    }
    
    public function loadAction(){
        $array = array();
        $this->view->addScriptPath(APPLICATION_PATH . "/views/scripts/");
        $array['html'] = $this->view->render('partials/feed.phtml'); // to render plain html
        $array['result'] = 1;
        $this->_helper->json($array);
    }

    public function refreshAction()
    {
        $array = array();
        
	    $registry = Zend_Registry::getInstance();
		$feedService=new Service_Feed();
		$feed=$feedService->getFeed($this->params);
		$array['json']=$this->view->feed=$feed['final_feed_result']; // to show map markers
		$this->view->category_tags=$feed['category_tags'];
        
        if (!empty($this->view->feed)) {
            $this->view->addScriptPath(APPLICATION_PATH . "/views/scripts/");
            $array['html'] = $this->view->render('partials/feed_refresh.phtml'); // to render plain html
            $array['result'] = 1;
            $this->_helper->json($array);
        } else {
            $this->_helper->json(array('result'=>0));
        }
    }

    public function loadMoreUserActivitiesAction() {
        $logService = new Service_Log();
        $this->view->logs = $logService->getActions(
            $this->params['user_id'], 
            $this->params['last_id'],
            Zend_Registry::get('config_ini')->para->feed->rpp
        );
        
        //specific action detail to be shown...
        $item_ids = array();
        $action_types = Zend_Registry::get('config_ini')->table->log_action->action_type->toArray();
        $object_types = Zend_Registry::get('config_ini')->table->log_action->object_type->toArray();
        foreach($this->view->logs as $log){
            if(
                ($log->action_type_id == $action_types['create'])
                    &&
                ($log->object_type_id == $object_types['event'])
            ){
             $item_ids[] = $log->object_id;
            }
        }
        
        $itemService=new Service_Item();
        $this->view->feed=$itemService->getItemsByIds($item_ids);
        
        $this->view->category_tags=$this->view->feed['category_tags'];
        
        $userService=new Service_User();
        $this->view->user=$userService->get($this->params['user_id'], Zend_Db::FETCH_OBJ);

        if (!empty($this->view->logs)) {
            $this->view->addScriptPath(APPLICATION_PATH . "/views/scripts/");
            $array['html'] = $this->view->render('partials/user_feed.phtml');
            $array['result'] = 1;
            $this->_helper->json($array);
        } else {
            $this->_helper->json(array('result'=>0));
        }
    }

    public function loadMoreTagFeedsAction() {
        $itemService=new Service_Item();
        $tag=
            $itemService->getItemBySlugName(
                urldecode($this->params['tag_slug_name']),
                array('tag','category_tag'),
                1
            );
            $this->view->tag=$tag['item'];
            $feedService=new Service_Feed();
            
            $feed_options = array('tag_id'=>$tag['item']->id);
            if (isset($this->params['last_id'])) $feed_options['last_id'] = $this->params['last_id'];
            $feed = $feedService->getFeed($feed_options);
            
            $this->view->feed=$feed['final_feed_result'];
            $this->view->category_tags=$feed['category_tags'];


        if (!empty($this->view->feed)) {
            Zend_Registry::set('page_type', 'tag');
            $this->view->addScriptPath(APPLICATION_PATH . "/views/scripts/");
            $array['html'] = $this->view->render('partials/feed_refresh.phtml');
            $array['result'] = 1;
            $this->_helper->json($array);
        } else {
            $this->_helper->json(array('result'=>0));
        }
        
    }


    public function loadMoreTreeFeedsAction() {
        $string='';
        $tagService=new Service_Tag();
        $cats=array();
        for ($i=1;$i<=$this->params['level'];$i++){
            $cats[]=urldecode($this->params['cat'.$i]);
        }
        $result=$tagService->getNameBySlugName($cats);
        if($this->params['level']==count($result)){
            $itemService=new Service_Item();
                $treeItem=
                    $itemService->getItemBySlugName(
                        $cats[count($cats)-1],
                        array('category_tag')
                    );
                $this->view->tree_item=$treeItem['item'];
    
                $tree=array();
                for ($i=1;$i<=$this->params['level'];$i++){
                    $tree[]=$result[$this->params['cat'.$i]]['id'];
                    $string.='/'.$this->params['cat'.$i];
                    $_SESSION['nav'][$result[$this->params['cat'.$i]]['name']]='/tree'.$string;
                }
    
                Zend_Registry::set('tree_ref', $tree);
                
                $this->view->result=1;
            
                $feed_options = array('tree'=>$tree);
                if (isset($this->params['last_id'])) $feed_options['last_id'] = $this->params['last_id'];
                
                $feedService=new Service_Feed();
                $feed=$feedService->getFeed($feed_options);
                
                $this->view->feed=$feed['final_feed_result'];
                $this->view->category_tags=$feed['category_tags'];
    
                $catService=new Service_Category();
                $this->view->related_categories=$catService->getMasterCategoriesFromTree($tree, true);
    
            if (!empty($this->view->feed)) {
                Zend_Registry::set('page_type', 'tree');
                $this->view->addScriptPath(APPLICATION_PATH . "/views/scripts/");
                $array['html'] = $this->view->render('partials/feed_refresh.phtml');
                $array['result'] = 1;
                $this->_helper->json($array);
            } else {
                $this->_helper->json(array('result'=>0));
            }
        }else{
            $this->_helper->json(array('result'=>0));
        }
    }
	public function deleteAction(){
    	$feedService=new Service_Feed();
    	$array['result']=$feedService->delete($this->params['id']);
		$this->_helper->json($array);
	}

    public function setStatusAction(){
    	$feedService=new Service_Feed();
    	$array['result']=$feedService->setStatus($this->params['id'],$this->params['set_status']);
		$this->_helper->json($array);
	}

}

