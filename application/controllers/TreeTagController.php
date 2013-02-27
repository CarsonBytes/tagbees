<?php
// this is to redirect to proper tree page
class TreeTagController extends Zend_Controller_Action
{
    protected $userId;
    
    public function preDispatch(){
       
    }
    
    public function init()
    {
    }
    
    public function indexAction()
    {
        if (trim($this->_request->getParam('slug_name'))==""){
            $this->_redirect('/');
        }else{
            Common::getSession()->nav=array(
                'Home' => '/'
            );
            $itemService = new Service_Item();
            $item = 
                $itemService->getItemBySlugName(
                    urldecode($this->_request->getParam('slug_name')),
                     'tree_tag'
                );
            
            
            if ($item == false){
                $this->_redirect('/');
            }
            
            $treeService = new Service_Tree();
            $tree = $treeService->getItemTreeById($item['item']->id);
            
            $tree_arr = array();
            foreach($tree['tree_details'] as $tree_detail){
                $tree_arr[array_search($tree_detail['id'], $tree['tree_ids'])] = $tree_detail['slug_name'];
            }
            
            $tree_str = '';
            for ($i=0; $i< count($tree_arr); $i++){
                $tree_str .= $tree_arr[$i];
                if ($i!=count($tree_arr)-1) $tree_str .='/';
            }
            
            $this->_redirect('tree/'.$tree_str.'/'.$this->_request->getParam('slug_name'));
        }
    }
}
