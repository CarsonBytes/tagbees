<?php
class Service_Feed{
	protected $identity;
	protected $db;
	public $feed_query;
	public $debug='';
    
    private $default_feed_para;
    
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		//$this->db->getProfiler()->setEnabled(true);
	}
	/*
	 *
	 */
	public function addFeedItem($type, $item_id,$old_input_tags_ids){
		$vars=array(
			'user_id'=>$this->identity->item_id,
			'type'=>$type,
			'item_id'=>$item_id,
			'tag_ids'=>implode(' ',$old_input_tags_ids),
			'create_time'=>date('Y-m-d H:i:s')
		);
		$this->db->insert('item',$vars);
	}
	/*
	 * set $rpp as 0 for unlimited!
     * @params $user_para
     *  $sort_by=0,
     *  $is_match_interest=0,
     *  $is_match_location=0,
     *  $place_lat=22.2,$place_lng=114.2,$radius=5,
     *  $user_id='',
     *  $tag_id='',
     *  $tag_range = '',
     *  $tree=array(),
     *  $q='',
     *  $begin_date='',
     *  $end_date='',
     *  $is_all_time=1,
     *  $rpp=20,
     *  $last_id=''
     *  $select_slug_name
     *  $used_ids,
     *  $is_random=false
     *  $is_bookmarked (true is only bookmarked, false is to only not bookmarked, undefined is both of them)
     * @params $sys_para
     *  max_distance = 50, 
     *  min_distance = 0.5, 
	 */
    public function getFeed($user_para = array(), $sys_para = array()){
        if ($sys_para == array()){
            $sys_para = Zend_Registry::get('config')->filter->sys_para->toArray();
        }
        
        if ($user_para == array()){
            // init default value
            $user_para = Zend_Registry::get('config')->filter->user_para->toArray();
            
        } else {
            if (!isset($user_para['rpp'])) $user_para['rpp'] =  Zend_Registry::get('config')->filter->user_para->rpp;
            if (isset($user_para['is_match_location'])) {
                if (!isset($user_para['place_lat'])) $user_para['place_lat'] =  Zend_Registry::get('config')->filter->user_para->place_lat;
                if (!isset($user_para['place_lng'])) $user_para['place_lng'] =  Zend_Registry::get('config')->filter->user_para->place_lng;
            }
        }
        //get general feed sql query and feed interest ids of the logined user
			$is_load_feed = true;	
			// get user interest
			// + check if $user_para['is_match_interest'] but no interest at all
			// 			then return array immediately
			$related_ids=array();
			if (Zend_Auth::getInstance()->hasIdentity() 
			 && isset($user_para['is_match_interest']) && $user_para['is_match_interest'] == 1){
				//get user followings and its tags
				//if ($user_para['sort_by']=="feed"){
					$user_group=$tag_group=array();
					$followings=array();
					if (!isset($user_para['user_id'])  && !isset($user_para['tag_id'])) { // only in frontpage when match_interest
						$tagService=new Service_Tag();
                        if (!isset($user_para['tag_range'])) $user_para['tag_range'] = '';
						$related_ids=$tagService->getUserTagIds($this->identity->item_id,1,$user_para['tag_range']);
						if (empty($related_ids)){
							$is_load_feed = false;
						}
					}
				//}
			}
             
		if ($is_load_feed){
			$select_cols=array('*');
			
			// is match location
			if (isset($user_para['is_match_location']) && $user_para['is_match_location']==1){
				$select_cols['distance']='( 6371 * acos( cos( radians('.$user_para['place_lat'].') ) * cos( radians( f.place_lat ) ) * cos( radians( f.place_lng ) - radians('.$user_para['place_lng'].') ) + sin( radians('.$user_para['place_lat'].') ) * sin( radians( f.place_lat ) ) ) )';
			}
	
	
			$this->feed_query=$this->db->select()
				->from(
					array('f'=>'item'),
					$select_cols
				)
				->joinLeft(array('i'=>'item'),
		                    'f.submitter_id = i.id',
							array(
				            	'submitter_name'=>'i.name',
				            	'submitter_slug_name'=>'i.slug_name'
							)
				);
	
      // get item tags
      $tagService=new Service_Tag();
      $this->feed_query = $tagService->getJoinQuery($this->feed_query, 'f.id');
      
      // get item images
      $imageService=new Service_Image();
      $this->feed_query = $imageService->getJoinQuery($this->feed_query, 'f.id');
  
      // get user event reminders
      $eventService=new Service_Event();
      $this->feed_query = $eventService->getJoinQuery($this->feed_query, 'f.id');
  
			$bookmarkService=new Service_Bookmark();
			$this->feed_query=$bookmarkService->getBookmarkCountQuery($this->feed_query,'f');
	
			if (Zend_Auth::getInstance()->hasIdentity()){
				$this->feed_query=$bookmarkService->getSelfIsBookmarkQuery($this->feed_query,'f');
			}
	
            //limit radius
			if (
			     isset($user_para['is_match_location']) && $user_para['is_match_location'] == 1 
			     && isset($user_para['radius']) && is_numeric($user_para['radius'])
             ){
				$this->feed_query->having('distance < '.$user_para['radius']);
			}
	           
            //match interest
			if (isset($user_para['is_match_interest']) && is_numeric($user_para['is_match_interest']) && $related_ids != array()){
				$this->feed_query->where('i.id in (?)',$related_ids);
			}
			
      // get by ids
      if (isset($user_para['item_ids']) && !empty($user_para['item_ids'])){
        $this->feed_query->where("f.id in (?)", $user_para['item_ids']);
      }

      if (isset($user_para['select_slug_name']) && !empty($user_para['select_slug_name'])){
        $this->feed_query->where("f.slug_name = ? ", $user_para['select_slug_name']);
      }

      
      if (isset($user_para['used_ids']) && !empty($user_para['used_ids'])){
        $this->feed_query->where('f.id not in (?)', $user_para['used_ids']);
      }
        			
			// general type(s) for feed
			$this->feed_query->where("f.type = 'event'");

			// display also disabled item of the loginned user
			if (Zend_Auth::getInstance()->hasIdentity()){
				$this->feed_query->where("f.status = 1 or f.submitter_id = ?",$this->identity->item_id);
			}else{
				$this->feed_query->where("f.status = 1");
			}
			// keyword filtering
			if (isset($user_para['q']) && trim($user_para['q']!='')){
				$terms_select=$this->db->select();
				$terms_select->where("f.name LIKE ?", '%'.$user_para['q'].'%')
					->orWhere("f.description LIKE ?", '%'.$user_para['q'].'%')
					->orWhere("f.place LIKE ?", '%'.$user_para['q'].'%')
					->orWhere("f.organiser_email LIKE ?", '%'.$user_para['q'].'%')
					->orWhere("f.organiser_website LIKE ?", '%'.$user_para['q'].'%');

				//including related items with similar tag name
				$tagId_nestedQuery=
					$this->db->select()
					->from(array('item_tag'),array('item_id'))
					->joinLeft(array('item'),'item.id=item_tag.tag_id',array(''))
					->where('item.name LIKE ?','%'.$user_para['q'].'%')
					->where('item.status = 1')
					->where('item_tag.status = 1')
					->where('type != ?','event');
				$terms_select->orWhere("f.id in ?", $tagId_nestedQuery);

				//including related item with category name
				$catId_nestedQuery=
					$this->db->select()
					->from(array('item'),array('id'))
					->where('item.name LIKE ?','%'.$user_para['q'].'%')
					->where('item.status = 1')
					->where('type != ?','event');
				$result=$this->db->fetchCol($catId_nestedQuery);
				foreach($result as $cat){
					$terms_select->orWhere("f.tree_ids LIKE '%|?|%'", $cat);
				}


				//including item with matched submitter name / username
				$username_nestedQuery=
					$this->db->select()
					->from(array('item'),array('id'))
					->where('item.name LIKE ? or item.slug_name LIKE ? ','%'.$user_para['q'].'%','%'.$user_para['q'].'%')
					->where('item.status = 1')
					->where('type != ?','event');
				$result=$this->db->fetchCol($username_nestedQuery);
				foreach($result as $id){
					$terms_select->orWhere("f.submitter_id = ?", $id);
				}

				$this->feed_query->where(join(" ",$terms_select->getPart(Zend_Db_Select::WHERE)));

			}
      
      // get user bookmarks
      if (isset($user_para['is_bookmarked'])){
        $this->feed_query
          ->joinLeft(
              array('ub'=>'user_bookmark'),
              'ub.item_id=f.id',
              array(
                  'ub_create_time' => 'create_time',
                  'ub_update_time' => 'update_time',
              )
          )
          ->where('ub.user_id = ?',$this->identity->item_id);
        if ($user_para['is_bookmarked']){
          $this->feed_query->where('ub.status > 0');
        } else{
          $this->feed_query->where('ub.status = 0');
        }
        if (($user_para['sort_by']) && $user_para['sort_by']=='event_time'){
          $this->feed_query->order('f.begin_datetime'. (isset ($user_para['order']) ? ' '.$user_para['order'] : ''));
        } else {
          $this->feed_query->order('ub.update_time'. (isset ($user_para['order']) ? ' '.$user_para['order'] : ''));
        }
      }

			//date range
			if (isset($user_para['is_all_time']) && $user_para['is_all_time']!=1){
				$date_select=$this->db->select();
				$i=0;
                $is_begin_date_specified = isset($user_para['begin_date']) && trim($user_para['begin_date']) !='';
                $is_end_date_specified = isset($user_para['end_date']) && trim($user_para['end_date']) !='';
                
                if($is_begin_date_specified){
                    $i++;
                    $date_select->where("DATEDIFF((f.end_datetime),?)>=0", $user_para['begin_date']);
                }
                if($is_end_date_specified){
                    $i++;
                    $date_select->where("DATEDIFF((f.begin_datetime),?)<=0",  $user_para['end_date']);
                }
				if($i>0)$this->feed_query->where(join(" ",$date_select->getPart(Zend_Db_Select::WHERE)));
			}

			//in case it is user / tag profile feed
			if (isset($user_para['user_id']) && $user_para['user_id'] !=''){
					//user profile!
					$this->feed_query->where("f.submitter_id = ?", intval($user_id));
			}else if (isset($user_para['tag_id']) && $user_para['tag_id'] !=''){
					//tag profile!
					$tagService=new Service_Tag();
					$this->feed_query=$tagService->getTaggedItemsNestedQuery($this->feed_query, 'f', $user_para['tag_id']);

			}else if (isset($user_para['tree']) && is_array($user_para['tree']) && !empty($user_para['tree'])){

				$tree_select=$this->db->select();
				$tree_select->where("f.tree_ids LIKE ?", '%|'.implode('|',$user_para['tree']).'|%');

				$serviceCat = new Service_Tree();
				$other_trees = $serviceCat->getSlaveCategoriesFromTree($user_para['tree']); //master sees slaves' feed
				if (isset($other_trees['related_cats'])){
					foreach($other_trees['related_cats'] as $value){
						$tree_select->orWhere("f.tree_ids LIKE ?", '%|'.implode('|',$value).'|%');
					}
				}
                
        // last tree tags related items
        $tagService=new Service_Tag();
        $tree_select=$tagService->getTaggedItemsNestedQuery($this->feed_query, 'f', end($user_para['tree']));

        $this->feed_query->where(join(" ",$tree_select->getPart(Zend_Db_Select::WHERE)));			
      }
      
      // feed type ordering and where clause preparation
      if (isset($user_para['sort_by']) && $user_para['sort_by']==1){
        //hot
                $this->feed_query->where('f.order_by_hot is not NULL');
                
                if (isset($user_para['last_id']) && is_numeric($user_para['last_id'])){
                    // load more feeds after last_id
                    $last_feed_sub_select = $this->db->select()
                        ->from('item',array('order_by_hot'))
                        ->where('id = ?', $user_para['last_id']);
                    $this->feed_query->where('f.order_by_hot > ?', $last_feed_sub_select);
                }
                
                $this->feed_query->order('f.order_by_hot asc');
                //$this->feed_query->order('like_count desc');
                $this->feed_query->order('f.id asc');
      }else if ( isset($user_para['sort_by']) && $user_para['sort_by']==2){
        //ending soon
                $this->feed_query->where('f.end_datetime >= NOW()');
                if (isset($user_para['last_id']) && $user_para['last_id']!=''){
                    // load more feeds after last_id
                    $last_feed_sub_select = $this->db->select()
                        ->from('item',array('end_datetime'))
                        ->where('id = ?', $user_para['last_id']);
                    $this->feed_query->where('f.end_datetime > ?', $last_feed_sub_select);
                }
        $this->feed_query->order('f.end_datetime asc');
                $this->feed_query->order('f.id asc');
      }else if ( isset($user_para['sort_by']) && $user_para['sort_by']=='random'){
        $this->feed_query->order("rand()"); // order randomly, may cause problem when it comes to MSSQL adapter
      } else /*if ( isset($user_para['sort_by']) && $user_para['sort_by']==3)*/{
        //new
        if (isset($user_para['last_id']) && $user_para['last_id']!=''){
            // load more feeds after last_id
            $last_feed_sub_select = $this->db->select()
                ->from('item',array('create_time'))
                ->where('id = ?', $user_para['last_id']);
            $this->feed_query->where('f.create_time < ?', $last_feed_sub_select);
        }
        $this->feed_query->order('f.create_time desc');
        $this->feed_query->order('f.id desc');
      } 

      // limit rpp
      if (isset($user_para['rpp']) && $user_para['rpp']!=0){
        $this->feed_query->limit($user_para['rpp']);
      }

			//fetch and pack result
      return $this->packFeeds($this->db->fetchAll($this->feed_query, array(),Zend_Db::FETCH_OBJ));
      
		}else{
			return array(
				'data'=>array(),
				//'comment'=>array(),
				//'image'=>array(),
				//'tags'=>array(),
				'tree_tags'=>array(),
				//'debug'=>$this->debug
			);
		}
	}

  public function packFeeds($feed_result){
  //$this->debug=$this->db->getProfiler()->getLastQueryProfile()->getQuery();
    // get involved cat ids
    $cat_ids=array();
    //$trees_ids['main'] = array();
    //$trees_ids['others'] = array();
    $data=array();
    foreach($feed_result as &$item){
      $itemService = new Service_Item();
      
      //get related categories
      $catService=new Service_Tree();
      $item->related_categories = $catService->getMasterCategoriesFromItem(
        $item->id
      );
      
      //get tree(s)
      $item = $itemService->getCatAndTreeIdsByCategoryIdsString($item->tree_ids, $item);
      $cat_ids = array_merge($cat_ids, $item->cat_ids);
      
      // encode all html related content to avoid annoying json parse error
      $item->description = urlencode($item->description);
      $item->datetime_note = urlencode($item->datetime_note);
      $item->price_note = urlencode($item->price_note);
      $item->application_datetime_note = urlencode($item->application_datetime_note);
      $item->organiser_detail = urlencode($item->organiser_detail);
      $item->traffic_note = urlencode($item->traffic_note);
      $item->reminder_title = urldecode($item->reminder_title);
      $item->reminder_description = urldecode($item->reminder_description);
      
      
      //calculate and insert linear direction between points. refer to application/../docs/long-place_lat-direction.bmp
      if (isset($user_para['is_match_location']) && $user_para['is_match_location']==1){
          if ($item->place_lat<$user_para['place_lat']){
              $base_lat=$item->place_lat;
          }else{
              $base_lat=$user_para['place_lat'];
          }
          $delta_lat=$item->place_lat-$user_para['place_lat'];
          $delta_lng=$item->place_lng-$user_para['place_lng'];
          $item->delta_lng=$delta_lng;
          $item->delta_lat=$delta_lat;
          $item->base_lat=$base_lat;
          $item->degree=intval(rad2deg(atan2($delta_lng * cos(deg2rad($base_lat)),$delta_lat)));
          if ($item->degree<0)$item->degree=$item->degree+360;
      }
      
      //check if tags are unqiue 
      $tag_slug_names = explode(',',$item->tag_slug_names);
      $tag_names = explode(',',$item->tag_names);
      $tag_types = explode(',',$item->tag_types);
      
      $unique_tags = array();
      $duplicate_index = array();
      for($i=0;$i<count($tag_slug_names);$i++){
          if (array_search($tag_slug_names[$i].';;'.$tag_types[$i], $unique_tags) == array()){
              $unique_tags[] = $tag_slug_names[$i].';;'.$tag_types[$i];
          } else {
              $duplicate_index[] = $i;
          }
      }
      
      //remove duplicate tags 
      foreach($duplicate_index as $value){
          unset($tag_slug_names[$value]);
          unset($tag_names[$value]);
          unset($tag_types[$value]);
      }
      $item->tag_slug_names=implode(',', $tag_slug_names);
      $item->tag_names=implode(',', $tag_names);
      $item->tag_types=implode(',', $tag_types);
      
      /*$item->images = $itemService->getImagePaths($item);
      unset($item->img_titles);
      unset($item->img_descriptions);
      unset($item->img_positions);
      unset($item->img_filenames);
      unset($item->img_is_main_pics);
      
      $item->tags_html = $itemService->getTagsHTML($item);
      unset($item->tag_names);
      unset($item->tag_slug_names);
      unset($item->tag_types);*/
      
      $data[]=$item;
  }
  
  
  $tree_tags=array();
  if (!empty($cat_ids)){
    //get category tags for items
    $tagService=new Service_Tag();
    $tree_tags=$tagService->getCategoryTags($cat_ids);
  }

  return array(
    'data'=>$data,
    //'comment'=>$comment,
    //'image'=>$image,
    //'tags'=>$tags,
    'tree_tags'=>$tree_tags,
    //'debug'=>$this->debug
  );
//$tags=$comment=$image=array();
//if (!empty($packed_result['item_ids'])){
  /* we use join query for item tags*/
//get tags for each item
//$tagService=new Service_Tag();
//$tags=$tagService->getItemTags($packed_result['item_ids']);

/* we use facebook comment plugin for simplicity */
//get comments for each item
//$commentService=new Service_Comment();
//$comment=$commentService->get($packed_result['item_ids']);

        /* we use join query for item images*/
//get image(s) for each item
//$imageService=new Service_Image();
//$image=$imageService->getImages($packed_result['item_ids']);
//}
  }

	public function updateBookmarkedUserCount($type,$item_id){
		$select = $this->db->select()
				->from(array('ui'=>'user_bookmark'),
					array('count'=>'count(*)')
              		)
              	->where('type = ?',$type)
              	->where('item_id = ?',$item_id)
              	->group(array('type','item_id'));
        if ($this->db->fetchOne($select)){
			$count= $this->db->fetchOne($select);
        }else{
        	$count= 0;
        }
       	$this->db->update('item', array('interested_user_count'=>$count), 'type='.$type.' and '.'item_id='.$item_id);
		return $count;
	}

	public function setStatus($id,$set_status){
		try {
			$var=array(
				'status'=>$set_status
			);
			$this->db->update('item',$var,'id='.$id);
            
            /*if ($set_status == -1){
                $actionService = new Service_Action();
                $actionService->updateAction($id, 0);
            }*/
            return 1;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}