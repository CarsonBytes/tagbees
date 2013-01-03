<?php
class Service_Tag{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
	public function add(){

	}
	public function getAllTags($status='',$include_item_number=true,$include_categories=false)
	{
		$select =  $this->db->select()
			->from(array('i'=>'item'),array('id','name'))
			->joinLeft(array('user_bookmark'),
				'i.id=user_bookmark.item_id',
				array('like_count'=>'count(user_bookmark.user_id)')
			)
			->group(array('user_bookmark.item_id'));

		$where=$this->db->quoteInto("i.type = ?",'tag');

		if ($status!=''){
			$where.=' AND ';
			$where.=$this->db->quoteInto("i.status = ?",1);
		}
		if ($include_item_number){
			$select->joinLeft(array('it'=>'item_tag'),
                    	'i.id = it.tag_id',
						array(
		                    'count' => 'COUNT(it.tag_id)'
						)
					)
				->group('i.id')
				->order('COUNT(*) DESC');
		}
		/*if (!$include_categories){
			$where.=' AND ';
			$where.=$this->db->quoteInto("i.is_category_tag = 0");
		}*/
		$select->where($where);
		$result=$this->db->fetchAll($select);
		/*$final_result=array();
		foreach($result as $value){
			$final_result[$value['id']]=$value;
		}
		return $final_result;*/
		return $result;
	}
	public function getUserTags($userId, $flagPositiveScore=1,$tagRange='')
	{
		$select =  $this->db->select()
			->from(array('user_interest'),array('tag_id','score'))
			->order(array('user_interest.score DESC', 'user_interest.update_time DESC', 'user_interest.create_time DESC'))
			->joinLeft('item', 'user_interest.tag_id=item.id',array('item.name','item.slug_name'));
		$where=$this->db->quoteInto("user_interest.user_id=?", $userId);
		$where.=' AND ';
		$where.='user_interest.status=1';
		/*$where.=' AND ';
		$where.='user_interest.type<>"promotion"';*/
		if($flagPositiveScore==1){
			$where.=' AND ';
			$where.='user_interest.score>0';
		}
		$select->where($where);
		if($tagRange!=''){
			$select->limit($tagRange);
		}
		$result=$this->db->fetchAll($select);
		return $result;
	}
	public function getUserTagIds($userId, $flagPositiveScore=1,$tagRange='')
	{
		$select =  $this->db->select()
			->from(array('user_interest'),array('tag_id'))
			->where("user_id=?", $userId)
			->where("status=1")
			//->where("type<>?","promotion")
			->order(array('user_interest.score desc','user_interest.update_time desc','user_interest.create_time desc'));
		if($flagPositiveScore==1){
			$select->where("score>0");
		}
		if($tagRange!=''){
			$select->limit($tagRange);
		}
		$result=$this->db->fetchCol($select);
		return $result;
	}
	public function getUserTagTotal($userId, $flagPositiveScore=1){
		$select =  $this->db->select()
			->from(array('user_interest'),array('count'=>'count(*)'))
			->group('user_id');
		$where=$this->db->quoteInto("user_interest.user_id=?", $userId);
		$where.=' AND ';
		$where.='user_interest.status=1';
		/*$where.=' AND ';
		$where.='user_bookmark.type<>"promotion"';*/
		if($flagPositiveScore==1){
			$where.=' AND ';
			$where.='user_interest.score>0';
		}
		$select->where($where);
		$result=$this->db->fetchOne($select);
		return $result;
	}

    public function getHotTags($timeframe='month'){
    	/*$result =  zend_shm_cache_fetch("tag::hot-{$timeframe}");
		if($result==null){*/
	    $ini = parse_ini_file(APPLICATION_PATH."/configs/application.ini");
    	$hottags_parameter['taggedWeight']=$ini["para.hotTag.taggedWeight"];
    	$hottags_parameter['likedWeight']=$ini["para.hotTag.likedWeight"];
			$select=$this->db->select()
	        			->from(array('i'=>'item'),array('id','name','slug_name','score'=>$hottags_parameter['likedWeight'].'*count(ul.user_id)+'.$hottags_parameter['taggedWeight'].'*count(ri.item_id)'))
	        			->where('i.status=1')
	        			->where('i.type<>"promotion"')
	        			->joinLeft(
	        				array('ul'=>'user_bookmark'),
	        				'ul.item_id=i.id',
	        				array('like_count'=>'count(ul.user_id)')
	        			)
	        			->joinLeft(
	        				array('ri'=>'item_tag'),
	        				'ri.tag_id=i.id',
	        				array('tag_count'=>'count(ri.item_id)')
	        			)
	        			->having('score>0')
	        			->group('i.id')
	        			->limit(10)
	        			->order("score desc");
	        if ($timeframe!='all'){
	        	$select->where("ul.update_time > (NOW()-INTERVAL 1 ".$timeframe.")");
	        }
	        $result=$this->db->fetchAll($select);
	       /* zend_shm_cache_store("tag::hot-{$timeframe}",$result ,24 * 3600);
		}*/
        return $result;
    }
    public function getNewTags(){
    	/*$result =  zend_shm_cache_fetch("tag::new");
    	if($result==null){*/
    	$select=$this->db->select()
        			->from(array('i'=>'item'),array('name','slug_name','create_time'=>'DATE(create_time)'))
        			->where('i.status=1')
        			->group('i.id')
        			->limit(10)
        			->order("i.create_time desc");
	        $result=$this->db->fetchAll($select);
	     /*   zend_shm_cache_store("tag::new",$result ,24 * 3600);
		}*/
        return $result;
    }

    public function getUserTotalPreferenceScore($userid){
    	$select=$this->db->select()
        			->from(array('user_bookmark'),array('score'))
        			->where('status=1')
        			->where('score>0')
        			->where('user_id = ?',$userid);
        $result=$this->db->fetchCol($select);
        $total=0;
        foreach($result as $score){
        	$total+=$score;
        }
        return $total;
    }
    public function getCategoryTags($cat_ids=array()){
		$cats=array();
        if (!empty($cat_ids)){
			$select=
					$this->db->select()
					->from(array('item'),
							array('id','name','slug_name'))
					->where('id in (?)',$cat_ids);
			$result=$this->db->fetchAll($select);
			foreach($result as $value){
				$cats[$value['id']]=array(
					'name'=>$value['name'],
					'slug_name'=>$value['slug_name']
				);
			}
			return $cats;
		}else{
            return false;
        }
	}
    public function getItemTags($item_ids=array()){
        if (!is_array($item_ids))$item_ids=array($item_ids);
        if (!empty($item_ids)){
            $item_tags = new StdClass;
            foreach($item_ids as $item_id){
				$select=
					$this->db->select()
					->from(array('item_tag'),
							array(
				            	'id'=>'tag_id'
							))
					->where('item_tag.item_id = ?',$item_id)
					->where('item_tag.status = 1')
					//->where('item_tag.type = "tag" OR item_tag.type = "new tag"')
					->joinLeft('item', 'item.id=item_tag.tag_id',array('name','slug_name','type'));

				$item_tags->{$item_id}=$this->db->fetchAll($select, array(),Zend_Db::FETCH_OBJ);
            }
			return $item_tags;
        }else{
            return false;
        }
    }
    
    public function getJoinQuery($select, $linked_item_id=''){
        return $select
                ->joinLeft(
                    array('it'=>'item_tag'),
                    'it.item_id='.$linked_item_id,
                    array(
                        'tag_names' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(i2t.name,''))"),
                        'tag_slug_names' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(i2t.slug_name,''))"),
                        'tag_types' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(i2t.type,''))")                        
                    )
                )
                ->joinLeft(
                    array('i2t'=>'item'),
                    'i2t.id=it.tag_id and it.status = 1',
                    array()
                )
                ->group($linked_item_id);
    }

	public function getTagBySlugName($slug_name){
		$select=
			$this->db->select()
			->from(array('item'),array('*'))
			->where('item.slug_name = ?',$slug_name)
			->joinLeft(array('image'),
                    'item.id = image.item_pic_id',
					array(
		            	'img_id'=>'id',
		            	'img_description'=>'description',
		            	'img_path'=>'path',
		            	'img_thumbnail_path'=>'thumbnail_path'
					)
			);

			if (Zend_Auth::getInstance()->hasIdentity()){
				$select=$bookmarkService->getSelfIsBookmarkQuery($select,'item');
			}
			$result['item']=$this->db->fetchRow($select);

		$select=
			$this->db->select()
			->from(array('image'),
					array(
		            	'id'=>'id',
		            	'description'=>'description',
		            	'path'=>'path',
		            	'thumbnail_path'=>'thumbnail_path'
					))
			->where('image.item_id = ?',$result['item']['id']);
		$result['image']=$this->db->fetchAll($select);

		$tagService=new Service_Tag();
		$result['tag']=$tagService->getItemTags($result['item']['id']);

		return $result;
	}
	public function getNameBySlugName($slug_name=array()){
        if (!is_array($slug_name))$slug_name=array($slug_name);
        if (!empty($slug_name)){
			$select=
				$this->db->select()
				->from(array('item'),array('id','name','slug_name'))
				->where('item.slug_name in (?)',$slug_name)
				->where('item.type in (?)',array('tag','category_tag'));
			$result=$this->db->fetchAll($select);
			$packed=array();
			foreach($result as $value){
				$packed[$value['slug_name']]=array(
					'id'=>$value['id'],
					'name'=>$value['name'],
				);
			}
			return $packed;
		}
		return array();
	}

	public function getTaggedItemsNestedQuery($select, $item_table_name ,$tag_ids){

		if (!is_array($tag_ids)){
			$tag_ids=array($tag_ids);
		}

		$tag_select=$this->db->select();
		$tagged_item_nested_query=$this->db->select()
				->from('item_tag',array('item_id'));
		$i=0;
		foreach($tag_ids as $tag_id){
			if ($i==0){
				$tagged_item_nested_query->where('item_tag.tag_id = ?',intval($tag_id));
				$tag_select
					->where($item_table_name.".category_ids LIKE '%|?|%'", intval($tag_id));

			}else{
				$tagged_item_nested_query->orWhere('tag_id = ?',intval($tag_id));
				$tag_select
					->orWhere($item_table_name.".category_ids LIKE '%|?|%'", intval($tag_id));
			}
			$i++;
		}
		$tag_select->orWhere($item_table_name.".id in (?)",$tagged_item_nested_query);

		return $select->where(join(" ",$tag_select->getPart(Zend_Db_Select::WHERE)));
	}

}