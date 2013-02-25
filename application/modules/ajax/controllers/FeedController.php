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
    }
    /*
    public function loadAction(){
        $array = array();
        $this->view->addScriptPath(APPLICATION_PATH . "/views/scripts/");
        $array['html'] = $this->view->render('partials/feed.phtml'); // to render plain html
        $array['result'] = 1;
        $this->_helper->json($array);
    }*/

    public function refreshAction()
    {
        $array = array();
        if ($this->params['type'] == 'index' || $this->params['type'] == 'tag_events'){
            $feedService=new Service_Feed();
            $this->_helper->json($feedService->getFeed($this->params));
        } else if ($this->params['type'] == 'user_log'){
            $logService = new Service_Log();
            $this->_helper->json($logService->getActions($this->params['user_id'], $this->params['last_id']));
        }else if ($this->params['type'] == 'tree_feeds'){
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
                            array('tree_tag')
                        );
                    $this->view->tree_item=$treeItem['item'];
        
                    $tree=array();
                    for ($i=1;$i<=$this->params['level'];$i++){
                        $tree[]=$result[$this->params['cat'.$i]]['id'];
                        $string.='/'.$this->params['cat'.$i];
                    }
                
                    $feedService=new Service_Feed();
                    $this->_helper->json($feedService->getFeed(array('tree'=>$tree, 'last_id'=>$this->params['last_id'])));
            }else{
                $this->_helper->json(array('result'=>0));
            }
        }
    }
/*
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
        
        $this->view->tree_tags=$this->view->feed['tree_tags'];
        
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
 */

    public function loadMoreTagFeedsAction() {
        $itemService=new Service_Item();
        $tag=
            $itemService->getItemBySlugName(
                urldecode($this->params['tag_slug_name']),
                array('tag','tree_tag'),
                1
            );
            $this->view->tag=$tag['item'];
            $feedService=new Service_Feed();
            
            $feed_para = array('tag_id'=>$tag['item']->id);
            if (isset($this->params['last_id'])) $feed_para['last_id'] = $this->params['last_id'];
            $feed = $feedService->getFeed($feed_para);
            
            $this->view->feed=$feed['final_feed_result'];
            $this->view->tree_tags=$feed['tree_tags'];


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

/*
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
                        array('tree_tag')
                    );
                $this->view->tree_item=$treeItem['item'];
    
                $tree=array();
                for ($i=1;$i<=$this->params['level'];$i++){
                    $tree[]=$result[$this->params['cat'.$i]]['id'];
                    $string.='/'.$this->params['cat'.$i];
                    Common::getSession()->nav[$result[$this->params['cat'.$i]]['name']]='/tree'.$string;
                }
    
                Zend_Registry::set('tree_ref', $tree);
                
                $this->view->result=1;
            
                $feed_para = array('tree'=>$tree);
                if (isset($this->params['last_id'])) $feed_para['last_id'] = $this->params['last_id'];
                
                $feedService=new Service_Feed();
                $feed=$feedService->getFeed($feed_para);
                
                $this->view->feed=$feed['final_feed_result'];
                $this->view->tree_tags=$feed['tree_tags'];
    
                $catService=new Service_Tree();
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
 */
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

