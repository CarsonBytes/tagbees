<?php
class TreeController extends Zend_Controller_Action
{
    protected $userId;
    
    public function preDispatch(){
       
    }
    
    public function init()
    {
    }
    
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/'
        );
        
        $string='';
        $tagService=new Service_Tag();
        $cats=array();
        for ($i=1;$i<=$this->_request->getParam('level');$i++){
            $cats[]=urldecode($this->_request->getParam('cat'.$i));
        }
        $result=$tagService->getNameBySlugName($cats);
        if($this->_request->getParam('level')==count($result)){
            $itemService=new Service_Item();
            $treeItem=
                $itemService->getItemBySlugName(
                    $cats[count($cats)-1],
                    array('category_tag')
                );
            $this->view->tree_item=$treeItem['item'];

            $tree=array();
            for ($i=1;$i<=$this->_request->getParam('level');$i++){
                $tree[]=$result[$this->_request->getParam('cat'.$i)]['id'];
                $string.='/'.$this->_request->getParam('cat'.$i);
                Common::getSession()->nav[$result[$this->_request->getParam('cat'.$i)]['name']]='/tree'.$string;
            }

            Zend_Registry::set('tree_ref', $tree);
            
            $this->view->result=1;
            
            $feedService=new Service_Feed();
            $this->view->tree_feeds = $feedService->getFeed(array('tree'=>$tree));
            //echo '<pre>';var_dump($this->view->tree_feeds);echo '</pre>';die();

            $catService=new Service_Category();
            $this->view->related_categories=$catService->getMasterCategoriesFromTree($tree, true);

            $i = 0;
            foreach(Common::getSession()->nav as $key => $value){
                if ($i>0) $this->view->headTitle($key);
                $i++;
            }
            $this->view->headTitle()->setSeparator(' > ');

        }else{
            $this->view->result=-1;
        }   
    }
}
