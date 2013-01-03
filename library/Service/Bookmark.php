<?php
class Service_Bookmark{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		$this->db->getProfiler()->setEnabled(true);
	}

	public function trigger($item_id,$set_status){
		if (!Zend_Auth::getInstance()->hasIdentity()) return false;
		try {
			//check if the item is already bookmarked / not
			$select=$this->db->select()
				->from('user_bookmark','status')
				->where ('user_id = ?',$this->identity->item_id)
				->where ('item_id = ?',$item_id);
			$result=$this->db->fetchRow($select);

			//(un)bookmarking...
			if ($result){
				if ($set_status==1){
					//bookmarking
					if($result['status']==0){
						$vars=array(
							'status'=>1,
							'update_time'=>date('Y-m-d H:i:s')
						);
						$where[]=$this->db->quoteInto('user_id = ?', $this->identity->item_id);
						$where[]=$this->db->quoteInto('item_id = ?', $item_id);
						$this->db->update('user_bookmark',$vars,$where);
					}
				}else{
					//unbookmarking
					if($result['status']==1){
						$vars=array(
							'status'=>0,
							'update_time'=>date('Y-m-d H:i:s')
						);
						$where[]=$this->db->quoteInto('user_id = ?', $this->identity->item_id);
						$where[]=$this->db->quoteInto('item_id = ?', $item_id);
						$this->db->update('user_bookmark',$vars,$where);
					}
					//return array('bookmark already not there');
				}
			}else{
				$vars=array(
					'user_id'=>$this->identity->item_id,
					'item_id'=>$item_id,
					'status'=>($set_status==1)? $set_status : 0,
					'create_time'=>date('Y-m-d H:i:s'),
					'update_time'=>date('Y-m-d H:i:s')
				);
				$this->db->insert('user_bookmark',$vars);
			}

			$interestService=new Service_Interest();
			$result=$interestService->getUserInterestScore();

			foreach($result as $value){
				$sql='INSERT INTO user_interest
					(user_id, tag_id, score,create_time)
					VALUES';
				$sql.='('.$this->identity->item_id.','. $value['tag_id'].','.$value['score'].',NOW())';
				$sql.=" ON DUPLICATE KEY UPDATE score=".$value['score'].", update_time=NOW()";
				$this->db->query($sql);
			}


			//save like log
			$select=$this->db->select()
					->from (array('item_tag'),'tag_id')
					->where ('item_id = ?',$item_id);
			$tags=$this->db->fetchCol($select);
			
            $logService = new Service_Log();
            if ($set_status == 1){
                $content = array(
                    'tag'=>$tags
                );
                $logService->addAction('bookmark', $item_id, 'event', $content);
            } else {
                $logService->updateAction($item_id, 0);
            }

			$select=
				$this->db->select()
				->from(array('item'),null)
				->where('id=?',$item_id);
            $select=$this->getBookmarkCountQuery($select);

            $result=$this->db->fetchOne($select);

			return $result;

		} catch (Exception $e) {
			return $e->getMessage();
		}
	}


	public function sendComment($item_id,$comment){
		if (!Zend_Auth::getInstance()->hasIdentity()) return false;
		try {
			$vars=array(
				'comment'=>$comment
			);
			$where[]=$this->db->quoteInto('user_id = ?', $this->identity->item_id);
			$where[]=$this->db->quoteInto('item_id = ?', $item_id);
			$this->db->update('user_bookmark',$vars,$where);
			return true;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/*
	 * tag_id, score
	 */
	public function getBookmarkedUserCount($item_array){
		$select =  $this->db->select()
			->from(array('ui'=>'user_bookmark'),array('type','item_id','count'=>'count(*)'));
		$i=0;
		$where='';
		foreach($item_array as $item){
			if ($i!=0) $where.=' OR ';
			$where.=$this->db->quoteInto("(type = ?", $item['type']);
			$where.=' AND ';
			$where.=$this->db->quoteInto("item_id = ?)", $item['item_id']);
			$i++;
		}
		//return $where;
		$select->where($where);
		$select->group(array('type','item_id'));

		$result=$this->db->fetchAll($select);
		if(count($result)>1){
			$array=array();
			foreach($result as $value){
				$array[$value['type'].'_'.$value['item_id']]=$value['count'];
			}
			return $array;
		}else if (count($result)==1){
			return $result[0]['count'];
		}else {
			return 0;
		}
	}
	/* all actions will be moved to log_action
     * public function saveLog($item_id,$tags,$status){
		if (Zend_Auth::getInstance()->hasIdentity()){
			$user_id=$this->identity->item_id;
		}else{
			return;
			//$vars['user_id']='';
		}
		return implode(' ',$tags);
		$vars=array(
				'item_id'=>$item_id,
				'user_id'=>$user_id,
				'tags'=>' '.implode(' ',$tags).' ',
				'status'=>$status,
				'ip'=>$_SERVER['REMOTE_ADDR'],
				'agent'=>$_SERVER['HTTP_USER_AGENT'],
				'create_time'=>date('Y-m-d H:i:s')
			);
		$this->db->insert('log_bookmark',$vars);
	}*/

	public function addTagsToSubmission($type,$submission_id,$input_tags){
		//check from db if those submitted tags already exist
	    $input_tags_names=explode(',',strtolower($input_tags));
		$select=$this->db->select()
					->from (array('t'=>'tag'), array('id','name','status','is_category_tag'))
					->where ('t.name in (?)',$input_tags_names);

		$old_input_tags=$this->db->fetchAll($select);


		//ensure old input tags are not category tags before assigning to the work with that id
		foreach ($old_input_tags as $value){
			if ($value['is_category_tag']==0){
				$data=array(
					'type'=>$type,	// work is of type 1, tutorial is of type 2
					'item_id'=>$submission_id,
				//	'status'=>1,
				/*
				 * -1: disabled (by advanced user / as spam)
					0: pending (e.g. others don't agree)
					1: enabled
				 */
					'tag_id'=>$value['id'],
					'tagger_id'=>$this->identity->item_id,
					'create_time'=>date('Y-m-d H:i:s')
				);
				$this->db->insert('item__tag',$data);
			}
		}

		//add new tags to db and set them to 0: 'pending' (for normal users only!!)
		$old_input_tags_names=array();
		$old_input_tags_ids=array();
		foreach ($old_input_tags as $value){
			$old_input_tags_names[]=$value['name'];
			$old_input_tags_ids[]=$value['id'];
		}
		$new_tags_names=array_diff($input_tags_names, $old_input_tags_names);
		$new_input_tags_ids=array();
		foreach ($new_tags_names as $value){
			//it's better to insert and handle any duplicate key errors.
			//same client can still insert the value during the period between your test search and insertion
			$sql = "INSERT IGNORE INTO tags (name,proposer_id, create_time) VALUES (?, ?, NOW())";
			$values = array("name"=>$value,"proposer_id"=>$this->identity->item_id);

			$this->db->query($sql, $values);
			$new_input_tags_ids[]=$this->db->lastInsertId('tag','id');
			//should follow up if someone has just added the new tags !!
		}

		//assign new input tags id to works
		foreach ($new_input_tags_ids as $value){
				$data=array(
					'type'=>$type,	// work is of type 1, tutorial is of type 2
					'item_id'=>$submission_id,
					//'status'=>0,
					'tag_id'=>$value,
					'tagger_id'=>$this->identity->item_id,
					'create_time'=>date('Y-m-d H:i:s')
				);
				$this->db->insert('item__tag',$data);
			}

		//return approved old tags for indexing purpose
		return array('ids'=>$old_input_tags_ids,'names'=>$old_input_tags_names);
	}


	public function getBookmarkCountQuery($select,$joined_left_item_table_name='item'){
		$like_count_nested_query=$this->db->select()
			->from('user_bookmark',array('item_id', 'like_count'=>'COUNT(user_bookmark.user_id)'))
			//->where('user_bookmark.score>0')
			->where('user_bookmark.status=1')
			->group('user_bookmark.item_id');
		return $select->joinLeft(
				array('ul'=>$like_count_nested_query),
				'ul.item_id='.$joined_left_item_table_name.'.id',
				array('like_count'=>'ifnull(like_count,0)')
			);

	}
	public function getSelfIsBookmarkQuery($select,$joined_left_item_table_name='item',$item_ids=''){
		$like_count_nested_query=$this->db->select()
			->from('user_bookmark',array('item_id','bookmark_comment'=>'comment','is_like'=>'COUNT(*)'))
			//->where('user_bookmark.score>0')
			->where('user_bookmark.status=1')
			->where('user_bookmark.user_id=?',$this->identity->item_id)
			->group('user_bookmark.item_id');
		if ($item_ids!=''){
			$like_count_nested_query->where('user_bookmark.item_id in (?)',$item_ids);
		}
		return $select->joinLeft(
				array('ul_self'=>$like_count_nested_query),
				'ul_self.item_id='.$joined_left_item_table_name.'.id',
				array('is_like'=>'ifnull(is_like,0)','bookmark_comment')
			);

	}
	public function getItemBookmarksList($item_id){
		$select=$this->db->select()
			->from(array('ul'=>'user_bookmark'),array('user_id'))
			->joinLeft(array('u'=>'user'), 'u.item_id=ul.user_id',array('username'))
			->joinLeft(array('i'=>'image'), 'i.item_pic_id=ul.user_id',array('thumbnail_path'))
			->where('ul.item_id=?',$item_id)
			//->where('ul.score>0')
			->where('ul.status=1');
		return $this->db->fetchAll($select);
	}
	public function getUserBookmark($user_id='', $bookmark_status=true,$fetch_mode=Zend_Db::FETCH_OBJ){
		if ($user_id==''){
			$user_id=$this->identity->item_id;
		}
		$select=$this->db->select()
			->from(array('user_bookmark'))
			->joinLeft(
				array('item'),
				'item.id=user_bookmark.item_id',
				array('name', 'slug_name', 'type', 'submitter_id', 'score', 'item_status' =>'status')
			)
			->joinLeft(
				array('item2'=>'item'),
				'item.submitter_id=item2.id',
				array('submitter_name'=>'name','submitter_slug_name'=>'slug_name')
			)
			->where('user_bookmark.user_id=?',$user_id)
			->order('user_bookmark.update_time desc');

		if ($bookmark_status) $select->where('user_bookmark.status > 0');
		$select=$this->getBookmarkCountQuery($select);
		return $this->db->fetchAll($select, array() ,$fetch_mode);
	}

	public function getUserBookmarkId($user_id='', $bookmark_status=true,$fetch_mode=Zend_Db::FETCH_OBJ){
		if ($user_id==''){
			$user_id=$this->identity->item_id;
		}
		$select=$this->db->select()
			->from(array('user_bookmark'))
			->where('user_bookmark.user_id=?',$user_id)
			->where('user_bookmark.status > 0');

		return $this->db->fetchCol($select, array() ,$fetch_mode);
	}

	public function getSuggestionList($user_id='', $total=10){
		if ($user_id=='' && Zend_Auth::getInstance()->hasIdentity()){
				$user_id=$this->identity->item_id;
		}

		$result=array();
		if ($user_id!=''){
			$interestService=new Service_Interest();
			$result=$interestService->getUserInterest($user_id, Zend_DB::FETCH_OBJ);
		}

		$select=$this->db->select()
			->from(array('item'))
			->where('type= ?','event')
			->order("rand()"); // order randomly, may cause problem when it comes to MSSQL adapter
		$select=$this->getBookmarkCountQuery($select);
		

		if ($result){ // if user has his/her own interest defined

			$select=$this->getSelfIsBookmarkQuery($select,'item');
			$select->where('type= ?','event');

			$bookmark = $this->getUserBookmarkId($user_id, true, Zend_Db::FETCH_ASSOC);

			$select->limit(rand(1, $total));
			// get tagged result of user interest first
			$interested_tag_ids=array();
			$tagService=new Service_Tag();
			foreach ($result as $value){
				if (!in_array($value->tag_id, $bookmark))
					$interested_tag_ids[]=$value->tag_id;
			}
			$select=$tagService->getTaggedItemsNestedQuery($select, 'item', $interested_tag_ids);

			$interested_result = $this->db->fetchAll($select);

			// get the remaining quotas of tagged result
			$select=$this->db->select()
				->from(array('item'))
				->where('type= ?','event')
				->order("rand()") // order randomly, may cause problem when it comes to MSSQL adapter
				->limit($total-count($interested_result));

			$select=$this->getBookmarkCountQuery($select);
		
			$normal_result =$this->db->fetchAll($select);

			//merge and shuffle result
			//$merged_result=array_merge($tagged_result, $untagged_result);
			//shuffle ($merged_result);

			return array(
				'intersted'=>$interested_result,
				'normal'=>$normal_result
			);

		}else{
			//if not simply return random suggestions
			$select->limit($total*2);

			return array(
				'normal'=>$this->db->fetchAll($select)
			);
		}

	}

	public function getUserBookmarkList($user_id='', $total=10, $fetch_mode=Zend_DB::FETCH_OBJ){
		if ($user_id==''){
			$user_id=$this->identity->item_id;
		}
		$select=$this->db->select()
			->from(array('user_bookmark'))
			->where('user_bookmark.user_id=?',$user_id)
			->joinLeft(array('item'), 'user_bookmark.item_id=item.id',array('name','slug_name','place','description','begin_datetime','end_datetime','not_time_specific','type'))
			//->where('user_bookmark.status > 0') // not neccessarily hide the unbookmarked items...
			->order(array('update_time desc', 'create_time desc'))
			->limit($total);

		$select=$this->getBookmarkCountQuery($select);
		$result = $this->db->fetchAll($select, array(), $fetch_mode);
        return $result;
	}
}