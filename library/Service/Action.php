<?php
class Service_Action{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
  
  /*
   * action_type : create, update, bookmark, tag
   * object_type : event, tag, user
   */
  public function addAction($action_type, $object_id, $object_type, $content = null, $status = 1, $user_id = null) {
      if ($user_id == null) {
          if (Zend_Auth::getInstance()->hasIdentity()){
              $user_id = $this->identity->item_id;
          } else {
              return false;
          }
      }
      if ($content != null) {
          $content = json_encode($content);
      }
      $data=array(
          'user_id'=>$user_id,
          'action_type'=>$action_type,
          'object_id'=>$object_id,
          'object_type'=>$object_type,
          'status'=>$status,
          'content'=>$content,
          'create_time'=>date('Y-m-d H:i:s'),
          'update_time'=>date('Y-m-d H:i:s')
      );
      return $this->db->insert('log_action',$data);
  }
    
    public function updateAction($object_id, $status) {
        $data=array(
            'status'=>$status,
            'update_time'=>date('Y-m-d H:i:s')
        );
        $where = array();
        $where[]=$this->db->quoteInto('object_id = ?', $object_id);
        return $this->db->update('log_action',$data, $where);
    }
    
    public function getActions($user_id, $last_id = NULL, $limit = NULL, $isShownActiveOnly = true, $action_type = NULL, $object_type = NULL) {
        if ($limit == null) $limit = Zend_Registry::get('config')->user_feed->rpp;
        $select = $this->db->select()
                    ->from('log_action')
                    ->where('user_id = ?',$user_id)
                    ->limit($limit)
                    ->joinLeft('item','item.id=log_action.user_id',array('user_name' => 'name','username' => 'slug_name'))
                    ->joinLeft(array('item2' => 'item'),'item2.id=log_action.object_id',array('object_name' => 'name','object_slug_name' => 'slug_name'))
                    ->order(array('log_action.update_time desc','log_action.create_time desc'));
        
        if ($isShownActiveOnly == true) {
            $select->where('log_action.status = 1');
            if (Zend_Auth::getInstance()->hasIdentity()){
                $select->where("log_action.user_id = ?",$this->identity->item_id);
            }
        }
        if ($last_id != NULL){
            // load more feeds after last_id
            $last_feed_sub_select = $this->db->select()
                ->from('log_action',array('update_time'))
                ->where('id = ?', $last_id);
            $select->where('log_action.update_time < ?', $last_feed_sub_select);
        }
        if ($action_type != NULL){
            $select->where('action_type = ?',$action_type);
        }
                    
        if ($object_type != NULL){
            $select->where('object_type = ?',$object_type);
        }
        $db_data = $this->db->fetchAll($select, array(),Zend_Db::FETCH_OBJ);
        
        $used_action_types = array();
        $used_object_types = array();
        
        foreach($db_data as &$log){
            //define which type of feeds should show events
            $log->is_detailed_feed = 
                (($log->action_type == 'create')
                &&
                ($log->object_type == 'event'))
                || 
                (($log->action_type == 'tag')
                &&
                ($log->object_type == 'event'));
            
            if ($log->is_detailed_feed) $feed_item_ids[] = $log->object_id;
            
            $log->content = json_decode($log->content);
            
            $used_object_types[]= $log->object_type;
            $used_action_types[]= $log->action_type;
        }
        //get and pack past tense and present tense of the verb   
        $action_types_data= array();
        foreach($used_action_types as $action_type){
          $action_types_data[$action_type] = array(
              //'present' =>  $action_type,
              'past' => Common::getPastTense($action_type)
          );
        }
        
        $itemService=new Service_Item();
        $feeds = $itemService->getItemsByIds($feed_item_ids);

        return array(
            'data' => $db_data,
            'user_id' => $user_id,
            'feeds' => $feeds,
            'action_types' => $action_types_data
        );
    }
}