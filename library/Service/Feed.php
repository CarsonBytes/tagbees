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
     *  $lat=22.2,$lng=114.2,$radius=5,
     *  $user_id='',
     *  $tag_id='',
     *  $tag_range = '',
     *  $tree=array(),
     *  $q='',
     *  $begin_date='',
     *  $end_date='',
     *  $all_time=1,
     *  $rpp=20,
     *  $last_id=''
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
            if (!isset($user_para['rpp'])) $user_para['rpp'] =  Zend_Registry::get('config')->filter->user_para->rpp;
            if (isset($user_para['is_match_location'])) {
                if (!isset($user_para['lat'])) $user_para['lat'] =  Zend_Registry::get('config')->filter->user_para->lat;
                if (!isset($user_para['lng'])) $user_para['lng'] =  Zend_Registry::get('config')->filter->user_para->lng;
            }
        } else {
            $user_para = Zend_Registry::get('config')->filter->user_para->toArray();
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
				$select_cols['distance']='( 6371 * acos( cos( radians('.$user_para['lat'].') ) * cos( radians( f.lat ) ) * cos( radians( f.lng ) - radians('.$user_para['lng'].') ) + sin( radians('.$user_para['lat'].') ) * sin( radians( f.lat ) ) ) )';
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
            // we should rewrite this as we no longer put the user thumbnail urls to image table
			// add image thumbnails
			//$imageService=new Service_Image();
			//$this->feed_query=$imageService->getJoinQuery($this->feed_query,'f.submitter_id', false);
	
			$bookmarkService=new Service_Bookmark();
			$this->feed_query=$bookmarkService->getBookmarkCountQuery($this->feed_query,'f');
	
			if (Zend_Auth::getInstance()->hasIdentity()){
				$this->feed_query=$bookmarkService->getSelfIsBookmarkQuery($this->feed_query,'f');
			}
	
			if (
			     isset($user_para['is_match_location']) && $user_para['is_match_location'] == 1 
			     && isset($user_para['radius']) && is_numeric($user_para['radius'])
             ){
				$this->feed_query->having('distance < '.$user_para['radius']);
			}
	
			if (isset($user_para['is_match_interest']) && is_numeric($user_para['is_match_interest'])){
				$this->feed_query->where('i.id in (?)',$related_ids);
			}
	
			//echo $user_para['sort_by'];
			//exit();
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
			}else{
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

			if (isset($user_para['rpp']) && $user_para['rpp']!=0){
				$this->feed_query->limit($user_para['rpp']);
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
			if (isset($user_para['q'])){
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
					$terms_select->orWhere("f.category_ids LIKE '%|?|%'", $cat);
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

			//date range
			if (isset($user_para['all_time']) && $user_para['all_time']!=1){
				$date_select=$this->db->select();
				$i=0;
				if(isset($user_para['begin_date'])){
					$i++;
					$date_select->where(" DATEDIFF((f.end_datetime),?)>=0", $user_para['begin_date']);
				}
                if(isset($user_para['end_date'])){
                    $i++;
                    $date_select->where("DATEDIFF((f.begin_datetime),?)<=0",  $user_para['end_date']);
                }
				if ($i!=0)$this->feed_query->where(join(" ",$date_select->getPart(Zend_Db_Select::WHERE)));
			}

			//in case it is user / tag profile feed
			if (isset($user_para['user_id'])){
					//user profile!
					$this->feed_query->where("f.submitter_id = ?", intval($user_id));
			}else if (isset($user_para['tag_id'])){
					//tag profile!
					$tagService=new Service_Tag();
					$this->feed_query=$tagService->getTaggedItemsNestedQuery($this->feed_query, 'f', $user_para['tag_id']);

			}else if (isset($user_para['tree']) && is_array($user_para['tree']) && !empty($user_para['tree'])){

				$tree_select=$this->db->select();
				$tree_select->where("f.category_ids LIKE ?", '%|'.implode('|',$user_para['tree']).'|%');

				$serviceCat = new Service_Category();
				$other_trees = $serviceCat->getSlaveCategoriesFromTree($user_para['tree']); //master sees slaves' feed
				if (isset($other_trees['related_cats'])){
					foreach($other_trees['related_cats'] as $value){
						$tree_select->orWhere("f.category_ids LIKE ?", '%|'.implode('|',$value).'|%');
					}
				}
				$this->feed_query->where(join(" ",$tree_select->getPart(Zend_Db_Select::WHERE)));
			}
            

			//fetch and pack result
			$feed_result=$this->db->fetchAll($this->feed_query, array(),Zend_Db::FETCH_OBJ);
            //$this->debug=$this->db->getProfiler()->getLastQueryProfile()->getQuery();
            
            // get involved cat ids
            $cat_ids=array();
            //$trees_ids['main'] = array();
            //$trees_ids['others'] = array();
            $data=array();
            foreach($feed_result as &$item){
                $itemService = new Service_Item();
                
                //get tree(s)
                $item = $itemService->getCatAndTreeIdsByCategoryIdsString($item->category_ids, $item);
                $cat_ids = array_merge($cat_ids, $item->cat_ids);
                
                $item->description = urlencode($item->description);
                //calculate and insert linear direction between points. refer to application/../docs/long-lat-direction.bmp
                if (isset($user_para['is_match_location']) && $user_para['is_match_location']==1){
                    if ($item->lat<$user_para['lat']){
                        $base_lat=$item->lat;
                    }else{
                        $base_lat=$user_para['lat'];
                    }
                    $delta_lat=$item->lat-$user_para['lat'];
                    $delta_lng=$item->lng-$user_para['lng'];
                    $item->delta_lng=$delta_lng;
                    $item->delta_lat=$delta_lat;
                    $item->base_lat=$base_lat;
                    $item->degree=intval(rad2deg(atan2($delta_lng * cos(deg2rad($base_lat)),$delta_lat)));
                    if ($item->degree<0)$item->degree=$item->degree+360;
                }
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
			$category_tags=array();
			if (!empty($cat_ids)){
				//get category tags for items
				$tagService=new Service_Tag();
				$category_tags=$tagService->getCategoryTags($cat_ids);
			}

			return array(
				'data'=>$data,
				//'comment'=>$comment,
				//'image'=>$image,
				//'tags'=>$tags,
				'category_tags'=>$category_tags,
				//'debug'=>$this->debug
			);
		}else{
			return array(
				'data'=>array(),
				//'comment'=>array(),
				//'image'=>array(),
				//'tags'=>array(),
				'category_tags'=>array(),
				//'debug'=>$this->debug
			);
		}
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
                $logService = new Service_Log();
                $logService->updateAction($id, 0);
            }*/
            return 1;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}