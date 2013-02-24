<?php
class Service_Item{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		$this->db->getProfiler()->setEnabled(true);
	}
	public function getSlugNameById($id){
		$select=
			$this->db->select()
			->from(array('item'),array('slug_name'))
			->where('item.id = ?',$id);
		$result=$this->db->fetchRow($select);

		return $result;
	}

    //get 1 item by slugname
	public function getItemBySlugName($slug_name, $type='event'){

		$select=
			$this->db->select()
			->from(array('item'),array('*'))
			->where('item.slug_name = ?',$slug_name)
			->joinLeft(array('item2'=>'item'),
                    'item2.id = item.submitter_id',
					array(
		            	'submitter_slug_name'=>'slug_name',
		            	'submitter_name'=>'name'
					)
			);

		$imageService=new Service_Image();
		$select=$imageService->getJoinQuery($select,'item.id');
        
        
        $tagService=new Service_Tag();
        $select=$tagService->getJoinQuery($select,'item.id');

		if($type != '' && $type != NULL){
		    if (is_string($type))
			    $select->where('item.type = ?',$type);
            else if (is_array($type) && !empty($type)) {
                $select->where('item.type in (?)',$type);
            }
		}
		$bookmarkService=new Service_Bookmark();
		$select=$bookmarkService->getBookmarkCountQuery($select);


		$result['item']=$this->db->fetchRow($select, array(), Zend_Db::FETCH_OBJ);
		if (!$result['item']){
			return false;
		}
        
        //$imageService=new Service_Image();
		//$result['image']=$imageService->getImages($result['item']->id);
        //$this->feed_query = $imageService->getJoinQuery($this->feed_query, 'f.id');
        $result['item']->images = $this->getImagePaths($result['item']);
        //$result['item']->tags_html = $this->getTagsHTML($result['item']);
		
		if ($result['item']->type == 'event'){
			if (Zend_Auth::getInstance()->hasIdentity()){
				$select=
					$this->db->select()
					->from(array('user_bookmark'),array('item_id','comment'))
					->where('user_id = ?',$this->identity->item_id)
					->where('item_id = ?',$result['item']->id)
					->where('status > 0'); // user should have bookmarked this item
				$userBookmarkResult=$this->db->fetchRow($select);
				if (!empty($userBookmarkResult)){
					$result['item']->is_like=1;
					$result['item']->bookmark_comment=$userBookmarkResult['comment'];
				}else{
					$result['item']->is_like=0;
				}
			}
		}

		//get related categories
		$catService=new Service_Tree();
		//$result['related_categories']=$catService->getRelatedCategoriesFromItem($result['item']->id);
		$result['related_categories']=$catService->getMasterCategoriesFromItem($result['item']->id);

		//get tree(s)
        $result['item'] = $this->getCatAndTreeIdsByCategoryIdsString($result['item']->category_ids, $result['item']);
        
        //get category tags information for the item
        $tree_tags=array();
        if (!empty($result['item']->cat_ids)){
            $tagService=new Service_Tag();
            $tree_tags=$tagService->getCategoryTags($result['item']->cat_ids);
        }
        $result['item']->tree_tags=$tree_tags;
        
        
		return $result;
	}

    public function getItemsByIds($item_ids = array()){
        if (!empty($item_ids)){
            $select=
                $this->db->select()
                ->from(array('item'),array('*'))
                ->where('item.id in (?)',$item_ids)
                ->joinLeft(array('item2'=>'item'),
                        'item2.id = item.submitter_id',
                        array(
                            'submitter_slug_name'=>'slug_name',
                            'submitter_name'=>'name'
                        )
                );
    
            $imageService=new Service_Image();
            $select=$imageService->getJoinQuery($select,'item.id');
            
            
            $tagService=new Service_Tag();
            $select=$tagService->getJoinQuery($select,'item.id');
    
            $bookmarkService=new Service_Bookmark();
            $select=$bookmarkService->getBookmarkCountQuery($select);
    
            
            $result=$this->db->fetchAll($select, array(), Zend_Db::FETCH_OBJ);
            if (!$result){
                return false;
            }
            
            $packed_result = array();
            $cat_ids=array();
            foreach ($result as $value) {
                $packed_result[$value->id] = clone $value;
                $packed_result[$value->id]->images = $this->getImagePaths($packed_result[$value->id]);
                //$packed_result[$value->id]->tags_html = $this->getTagsHTML($packed_result[$value->id]);
                
                //get tree(s)
                $item = $this->getCatAndTreeIdsByCategoryIdsString($packed_result[$value->id]->category_ids, $item);
                $cat_ids = array_merge($cat_ids, $item->cat_ids);
                
                
                if ($packed_result[$value->id]->type == 'event'){
                    if (Zend_Auth::getInstance()->hasIdentity()){
                        $select=
                            $this->db->select()
                            ->from(array('user_bookmark'),array('item_id','comment'))
                            ->where('user_id = ?',$this->identity->item_id)
                            ->where('item_id = ?',$packed_result[$value->id]->id)
                            ->where('status > 0'); // user should have bookmarked this item
                        $userBookmarkResult=$this->db->fetchRow($select);
                        if (!empty($userBookmarkResult)){
                            $packed_result[$value->id]->is_like=1;
                            $packed_result[$value->id]->bookmark_comment=$userBookmarkResult['comment'];
                        }else{
                            $packed_result[$value->id]->is_like=0;
                        }
                    }
                }
        
                //get related categories
                $catService=new Service_Tree();
                $packed_result[$value->id]->related_categories=$catService->getMasterCategoriesFromItem($packed_result[$value->id]->id);
        
                //get tree(s)
                $packed_result[$value->id] = $this->getCatAndTreeIdsByCategoryIdsString($packed_result[$value->id]->category_ids, $packed_result[$value->id]);
                
                //get category tags information for the item
                $tree_tags=array();
                if (!empty($packed_result[$value->id]->cat_ids)){
                    $tagService=new Service_Tag();
                    $tree_tags=$tagService->getCategoryTags($packed_result[$value->id]->cat_ids);
                }
                $packed_result[$value->id]->tree_tags=$tree_tags;
            }
            
            if (!empty($cat_ids)){
                //get category tags for items
                $tagService=new Service_Tag();
                $packed_result['tree_tags']=$tagService->getCategoryTags($cat_ids);
            }
            
            return $packed_result;
        }
        return false;
    }

    public function getImagePaths($item)
    {
        if (isset ($item->img_filenames)) 
            $img_filenames = explode(',',$item->img_filenames);
        
        $main_img_info = $img_info = array();
        if (isset ($img_filenames) && ($img_filenames[0] != '')) {
            $filenames = array(); // to check duplicates
            $img_info = array();
            $img_titles = explode(',',$item->img_titles);
            $img_descriptions = explode(',',$item->img_descriptions);
            $img_is_main_pics = explode(',',$item->img_is_main_pics);
            $img_positions = explode(',',$item->img_positions);
            foreach ($img_filenames as $key => $filename) {
                if (in_array($filename, $filenames)) continue; // duplicated filename and skip
                $filenames[] = $filename;
                $data = array(
                    'url' => Common::getUploadedImageUrl($filename),
                    'title' => $img_titles[$key],
                    'description' => $img_descriptions[$key],
                    'position' => $img_positions[$key]
                );
                if ( $img_is_main_pics[$key] == 1 )
                    $main_img_info[] = $data;
                else
                    $img_info[] = $data;
            }
        } 

        return array('main'=>$main_img_info, 'general' => $img_info);
    }

    /*
    public function getTagsHTML($item) {
        $tag_names = explode(',',$item->tag_names);
        $tag_slug_names = explode(',',$item->tag_slug_names);
        $tag_types = explode(',',$item->tag_types);
        $tags_array = array(); // this is to check duplicates
        $tags_html_array = array();
        for ($i=0;$i<count($tag_names);$i++) {
            
            if (isset($tag_names[$i]) && $tag_names[$i]!='' &&
                isset($tag_slug_names[$i]) && $tag_slug_names[$i]!='' && 
                isset($tag_types[$i]) && $tag_types[$i]!='') {
                    
                 /* check duplicates 
                 if (!in_array($tag_slug_names[$i].'||'.$tag_types[$i], $tags_array)) {
                     $tag_type = ($tag_types[$i] == 'tree_tag') ? 'tag' : $tag_types[$i];
                     $tags_array[] = $tag_slug_names[$i].'||'.$tag_types[$i];
                     $tags_html_array[] = '<a class="ajax_load" href="' . Zend_Controller_Front::getInstance()->getBaseUrl().'/'.$tag_type . '/' . $tag_slug_names[$i] . '">' . $tag_names[$i] . '</a>';
                 }
            }
        }
        return implode(', ',$tags_html_array);
    }
     */

    public function getCatAndTreeIdsByCategoryIdsString($category_ids_string, &$item){
        $cat_ids = array();
        $tree_ids['main'] = array();
        $tree_ids['others'] = array();
        if ($category_ids_string != ''){
            $trees = explode(';', $category_ids_string);
            $tree_ref = (Zend_Registry::isRegistered('tree_ref') && is_array(Zend_Registry::get('tree_ref'))) ? Zend_Registry::get('tree_ref') : array();
            
            foreach($trees as $tree){
                $tree_array = array_diff(explode('|', $tree), array('1',''));
                $cat_ids = array_merge($cat_ids, $tree_array);
                
                if ($tree_ids['main'] == array()) {
                    
                    if (empty($tree_ref)){
                        $tree_ids['main'] = $tree_array;
                    } else if (array_diff($tree_ref, $tree_array) == array()){
                        $tree_ids['main'] = $tree_array;
                    } else {
                        $tree_ids['others'][] = $tree_array;
                    }
                    
                } else {
                    $tree_ids['others'][] = $tree_array;
                }
                
            }
            
            // in case there are no matches with $tree_ref and $tree_array, then we take the first one by default
            if (empty($tree_ids['main']) && isset($tree_ids['others'][0])) {
                $tree_ids['main'] = $tree_ids['others'][0];
                unset($tree_ids['others'][0]);
            }
        }
        $item->cat_ids = $cat_ids;
        $item->tree_ids = $tree_ids;
        return $item;
    }

	public function get($item_id){
		$select=
			$this->db->select()
			->from(
				array('f'=>'item'),
				array('*')
			)
			->joinLeft(array('i'=>'item'),
	                    'f.submitter_id = i.id',
						array(
			            	'submitter_name'=>'i.name',
			            	'submitter_slug_name'=>'i.slug_name'/*,
			            	'submitter_score'=>'i.score',*/
						)
				)
			/*->joinLeft(array('payment_method'),
	                    'f.payment_method_id = payment_method.id',
						array(
			            	'cash','bank_transfer','credit_card'
						)
				)
			->joinLeft(array('user_like'),
	                    'f.id = user_like.item_id',
						array(
			            	'item_id'
						)
				)*/
			->where('f.id = ?',$item_id);

		$imageService=new Service_Image();
		$select=$imageService->getJoinQuery($select,'f.submitter_id');

		$bookmarkService=new Service_Bookmark();
		$select=$bookmarkService->getBookmarkCountQuery($select,'f');
		if (Zend_Auth::getInstance()->hasIdentity()){
			$select=$bookmarkService->getSelfIsBookmarkQuery($select,'f',$item_id);
		}

		$item=$this->db->fetchRow($select);

		if ($item['is_like']!=0){
			$item['is_like']=1;
		}else{
			$item['is_like']=0;
		}

		return $item;
	}


	public function getPicture($pic_id){
		$select = $this->db->select()
							->from('image','*')
							->where('id = ?',$pic_id);
		return $this->db->fetchRow($select);
	}

	public function getAll(){
		/*$final_result =  zend_shm_cache_fetch("item::all");
		if ($final_result == null) {*/
			$select =  $this->db->select()
				->from(array('i'=>'item'),array('id','name','slug_name','type'));
			$result=$this->db->fetchAll($select);
			$final_result=array();
			foreach($result as $value){
				$final_result[$value['id']]=$value;
			}
		/*	zend_shm_cache_store("item::all",$final_result,24*3600);
		}*/

		return $final_result;
	}

	public function getCategoryIdByName($name){
		$select = $this->db->select()
							->from('item','id')
							->where('name = ?',$name)
							->where("type = 'tree_tag'");
		return $this->db->fetchOne($select);
	}

	public static function getIdBySlugName($slug_name){
		$select = $this->db->select()
							->from('item','id')
							->where('slug_name = ?',$slug_name);
		return $this->db->fetchOne($select);
	}
}