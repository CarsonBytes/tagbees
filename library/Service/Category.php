<?php

class Service_Category{
	protected $db;
	/*
	 * $category:
	 * array(
	 * 	array(
	 * 		'name'=>'Meals',
	 * 		'slug_name'=>'meals',
	 * 		'subcategories'=>array()
	 * 	),
	 * 	array(
	 * 		'name'=>'Fashion',
	 * 		'slug_name'=>'fashion',
	 * 		'subcategories'=>array()
	 * 	)
	 * )
	 */
	private $table='item';
	private $tree_table='category_tree';
	private $first_cat_import_flag=1;
	private $submitter_id;
	private $category;
	private $last_id=0;
	private $levels=array();
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();

	    /* Deprecated*/
	    //$this->category=Common::config()->category->toArray();

		if (Zend_Auth::getInstance()->hasIdentity()){
			$this->submitter_id = $this->identity->item_id;
		}else{
			$this->submitter_id=0;
		}
		//$this->db->getProfiler()->setEnabled(true);
	}
	public function get($is_all_included=true){
	    $query=$this->db->select()
		    ->from('item',array('id','name','category_ids'))
		    ->where('type=?','category_tag')
		    ->where('name<>?','ROOT')
		    ->order(array('category_ids','id'));

		/*if (! $is_all_included){
			//@to-do may need a new field to store the hide status, coz it's not quite appropriate to place it in description
			$query->where('description not like ?', '%hide%');
		}*/
	    return $this->db->fetchAll($query);
	}
	public function get3LevelCategories(){
	    $query=$this->db->select()
		    ->from('item',array('id','name','slug_name','category_ids'))
		    ->where('type=?','category_tag')
		    ->where('name<>?','ROOT')
		    ->order(array('category_ids','id'));
	    $result=$this->db->fetchAll($query);

	    $cat=array();
	    foreach($result as $row){
	        $level=count(explode('|', $row['category_ids']))-2;
	        $cat[$level][]=$row;
	    }

	    return $cat;
	}
	/* not updated related categories logic
	public function getRelatedCategoriesFromTree($tree){
		$tree_string="|1|".implode("|", $tree)."|";
	    $query=$this->db->select()
				->from("tree_master",array('trees'))
				->where("master = ?", $tree_string);
		$result=$this->db->fetchOne($query);

		if ($result!=null){
			return $this->__packTree($tree_string,$result);
        }else{
			return false;
        }
	}
	*/

	public function getMasterCategoriesFromTree($tree, $is_all_included=false){
		$tree_string="|1|".implode("|", $tree)."|";

		return $this->__packMasterTrees($tree_string, $is_all_included);
	}

	public function getMasterCategoriesFromItem($item_id, $is_all_included=false){
	    $query=$this->db->select()
				->from("item",array('category_ids'))
				->where("id = ?", $item_id);
		$result=$this->db->fetchOne($query);
		$tree_string="|1".$result;

		return $this->__packMasterTrees($tree_string, $is_all_included);
	}

	public function getSlaveCategoriesFromTree($tree, $is_all_included=true){
		$tree_string="|1|".implode("|", $tree)."|";

		return $this->__packSlavesTrees($tree_string, $is_all_included);
	}


	private function __packSlavesTrees($master_tree_string, $is_all_included){
	    $query=$this->db->select()
				->from("tree_master",array('trees'))
				->where("master like ?", "%".$master_tree_string."%");
		$slaves_cats=$this->db->fetchOne($query);

		$cats_string=explode(';',$slaves_cats);
		array_pop($cats_string);
		array_shift($cats_string);

		$related_cats = array();
		foreach($cats_string as $key => $cat_string){
			$related_cats[]=explode('|',$cat_string);
		}

		$cats=array();
		foreach($related_cats as &$cat){
			array_pop($cat);
			array_shift($cat);
			if ($cat[0]==1) unset ($cat[0]);
			$cats[]=$cat;
		}
		if (!empty($cats)){
			$query=$this->db->select()
					->from("item",array('id','slug_name','name'))
					->where("id in (?)", $cats);
			$result=$this->db->fetchAll($query, array(), Zend_Db::FETCH_OBJ);

			$packed=array();
			foreach($result as $key => $value){
					$packed[$value->id]=
						array(
							'slug_name'=>$value->slug_name,
							'name'=>$value->name
						);
			}
			return array(
					'cat_info' => $packed,
					'related_cats' => $related_cats
				);
		}else{
			return false;
		}
	}

	private function __packMasterTrees($tree_string, $is_all_included){
	    $query=$this->db->select()
				->from("tree_master",array('master'))
				->where("trees like ?", "%;".$tree_string.";%");
		if (! $is_all_included){
			//@to-do may need a new field to store the hide status, coz it's not quite appropriate to place it in description
			$query->joinLeft('item', "tree_master.id=item.id",array())
					->where('item.description not like ?', '%hide%');
		}
		$masters=$this->db->fetchCol($query);


		$related_cats = array();
		foreach($masters as $key => $cat_string){
			if ($tree_string==$cat_string) unset($cats_string[$key]);
			else $related_cats[]=explode('|',$cat_string);
		}
		if (!empty($related_cats)){
			$cats=array();
			foreach($related_cats as &$cat){
				array_pop($cat);
				array_shift($cat);
				if ($cat[0]==1) unset ($cat[0]);
				$cats[]=$cat;
			}

			$query=$this->db->select()
					->from("item",array('id','slug_name','name'))
					->where("id in (?)", $cats);
			$result=$this->db->fetchAll($query, array(), Zend_Db::FETCH_OBJ);

			$packed=array();
			foreach($result as $key => $value){
					$packed[$value->id]=
						array(
							'slug_name'=>$value->slug_name,
							'name'=>$value->name
						);
			}

			return array(
					'cat_info' => $packed,
					'related_cats' => $related_cats
				);
		}else{
			return false;
		}
	}

	/* not updated related categories logic
	public function getRelatedCategoriesFromItem($item_id){
	    $query=$this->db->select()
				->from("item",array('category_ids'))
				->where("id = ?", $item_id);
		$result=$this->db->fetchOne($query);
		$tree_string="|1".$result;


	    $query=$this->db->select()
				->from("tree_master",array('trees'))
				->where("trees like ?", "%;".$tree_string.";%");
		$result=$this->db->fetchOne($query);

		if ($result!=null){
			return $this->__packTree($tree_string,$result);
        }else{
            return false;
        }

	}

	private function __packTree($tree_string,$result){
		$cats_string=explode(';',$result);
		array_pop($cats_string);
		array_shift($cats_string);

		$related_cats = array();
		foreach($cats_string as $key => $cat_string){
			if ($tree_string==$cat_string) unset($cats_string[$key]);
			else $related_cats[]=explode('|',$cat_string);
		}

		$cats=array();
		foreach($related_cats as &$cat){
			array_pop($cat);
			array_shift($cat);
			if ($cat[0]==1) unset ($cat[0]);
			$cats[]=$cat;
		}


		$query=$this->db->select()
				->from("item",array('id','slug_name','name'))
				->where("id in (?)", $cats);
		$result=$this->db->fetchAll($query, array(), Zend_Db::FETCH_OBJ);

		$packed=array();
		foreach($result as $key => $value){
				$packed[$value->id]=
					array(
						'slug_name'=>$value->slug_name,
						'name'=>$value->name
					);
		}
		return array(
				'cat_info' => $packed,
				'related_cats' => $related_cats
			);
	}
	*/


	public function add($array=null,$parent_ids=array(1),$level=0){
		if (is_null($array)) $array=$this->category;

		//add root category
		if($this->first_cat_import_flag){
			$selectRootIdQuery=$this->db->select()
					->from($this->table,'id')
					->where('id=1');
				$this->last_id=$this->db->fetchOne($selectRootIdQuery);
				$rootData=array(
					'id'=>1,
					'name'=>'ROOT',
					'slug_name'=>'ROOT',
					'type'=>'category_tag',
					'submitter_id'=>$this->submitter_id,
					'category_ids'=>'|0|'
				);
				if ($this->last_id==''){
					$rootData['create_time']=date('Y/m/d H:i:s');

					$this->db->insert($this->table, $rootData);
					$this->last_id=$this->db->lastInsertId();
					$status='inserted';
				}else{
					$rootData['update_time']=date('Y/m/d H:i:s');
					$this->db->update($this->table, $rootData, "id=1" );

					$selectIdQuery=$this->db->select()
						->from($this->table,'id')
						->where('id=0');
					$this->last_id=$this->db->fetchOne($selectIdQuery);
					$status='updated';
				}
			echo '<br />'.$status.' root category '.$this->last_id;
			var_dump($rootData);
			$this->first_cat_import_flag--;
		}

		foreach($array as $key=>$value){
			while($level<count($parent_ids)-1){
				array_pop($parent_ids);
			}
			if (is_array($value)){
				$parent_ids[]=$this->addQuery($key,$parent_ids);
				if (!empty($value)) {
					$this->add($value,$parent_ids,$level+1);
				}else{
					echo 'skipped empty array/name from the above category! <br />';
				}
			}else{
				$this->addQuery($value,$parent_ids);
			}
		}
	}
	private function addQuery($name,$parent_ids){
		$common= new Common();
		$slug=$common->getSlug($name);
		$values=array(
				'name'=>$name,
				'slug_name'=>$slug,
				'type'=>'category_tag',
				'submitter_id'=>$this->submitter_id,
				'category_ids'=>"|".implode("|", $parent_ids)."|"
			);
		$selectIdQuery=$this->db->select()
			->from($this->table,'id')
			->where('slug_name=?',$slug)
			->where('type=?','category_tag');
		$this->last_id=$this->db->fetchOne($selectIdQuery);
		if ($this->last_id==''){
			$values['create_time']=date('Y/m/d H:i:s');

			$this->db->insert($this->table, $values);
			$this->last_id=$this->db->lastInsertId();
			$status='inserted';
		}else{
			$values['update_time']=date('Y/m/d H:i:s');
			$this->db->update($this->table, $values, "type='category_tag' and slug_name='".$slug."'" );

			$selectIdQuery=$this->db->select()
				->from($this->table,'id')
				->where('slug_name=?',$slug)
				->where('type=?','category_tag');
			$this->last_id=$this->db->fetchOne($selectIdQuery);
			$status='updated';
		}
		echo '<br />'.$status.' category '.$this->last_id.': '.$name;
		var_dump($values);
		/* Abandon this because the last insert id is not correct in any cases
		$select="INSERT INTO ".$this->table." (name, slug_name, status, type, submitter_id,category_ids,create_time)
			VALUES (?, ?, 1,'category_tag', ?, ?,NOW())
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=?,status=1, category_ids=?,update_time=NOW()";
		$value=array($name, $slug, $this->submitter_id, $category_ids,$name,$category_ids);
		$result=$this->db->query($select,$value);
		if ($result->rowCount() == 1){ //INSERT
			$status='inserted';
		}elseif ($result->rowCount() == 2){ //UPDATE
			$status='updated';
		}
		$selectIdQuery=$this->db->select()
			->from($this->table,'id')
			->where('slug_name=?',$slug);
		*/
		return $this->last_id;
	}

	public function rebuildTreeFromItem(){
		$this->db->query('truncate '.$this->tree_table);
		$query=$this->db->select()
			->from($this->table,array('id','name','slug_name','category_ids','category_position'))
			->where('type=?','category_tag')
			->order('id');
		$result=$this->db->fetchAll($query);
		foreach($result as $value){
			$categories=explode('|',$value['category_ids']);
			end($categories);
			$insert=array(
				'id'=>$value['id'],
				'title'=>$value['name'],
				'parent_id'=>prev($categories),
				'slug_name'=>$value['slug_name'],
				'level'=>count($categories)-2,
				'position'=>$value['category_position'],
				'create_time'=>date('Y/m/d H:i:s')
			);
			//error_log(print_r($insert,1));
			$this->db->insert($this->tree_table,$insert);
		}
		$this->reconstructTree();
	}
	/*
	 * provided that no category title is the same
	 */

	public function updateItemFromTree(){
		$query=$this->db->select()
			->from($this->tree_table,array('id','title','parent_id','level','position'))
			->where('level!=0')
			->order('level');
		$result=$this->db->fetchAll($query);

		foreach($result as $item){
			//scan items for each level and store them in to 1 array 'levels'
			$this->levels[$item['level']][$item['id']]=
				array(
					'parent_id'=>$item['parent_id'],
					'title'=>$item['title']
				);
		}
		$keys = array_keys($this->levels);
		$level_count = end($keys);
		$this->category=array();
		foreach($this->levels['1'] as $key=>$value)
			$this->category[$value['title']]='';

		for ($i=2;$i<=$level_count;$i++){
			array_walk_recursive($this->category, 'self::insertSubCat',$i);
		}
		array_walk_recursive($this->category, 'self::insertSubCat','end');
		//var_dump($this->category);
		$this->add();
	}

	private function insertSubCat(&$value, $key, $i){
		if ($i!='end'){
			foreach ($this->levels[$i] as $id=>$info){
				if ($key==$this->levels
							[$i-1]
							[$info['parent_id']]
							['title']
				){
					$value[$info['title']]='';
				}
			}
		}else{
			if ($value=='')$value=array();
		}
	}


	public function clean($new_names){
		$query = $this->db->select()
			->from($this->table,"name")
			->where("status=1")
			->where("type='category_tag'");

		$result = $this->db->fetchAll($query);

		$used_names=array();
		foreach ($result as $row) {
		    $used_names[]= $row['name'];
		}
		$diff = array_diff($used_names,$new_names);
		foreach($diff as $value){
			$where=array(
				'name'=>$value,
				'type'=>'category_tag'
			);
			$query=$this->db->update($this->table,array('status'=>0),'name='.$value.' and type="category_tag"');
			echo ("A category tag '$value' is disabled.");
			echo '<br />';
		}
	}

	private function reconstructTree(){
		$front=Zend_Controller_Front::getInstance();
		// create a new cURL resource
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, 'http://'.$_SERVER['HTTP_HOST'].$front->getBaseUrl().'/jstree_pre1.0_fix_1/_demo/server.php?reconstruct');
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// grab URL and pass it to the browser
		curl_exec($ch);

		// close cURL resource, and free up system resources
		curl_close($ch);
	}

	public function browse($cat_ids=array(1)){
		//$this->db->getProfiler()->setEnabled(true);
		$category_ids="|".implode("|", $cat_ids)."|";
		$query = $this->db->select()
			->from('item',array('id','name'))
			->where('category_ids = ?',$category_ids)
			->where('type=?','category_tag');
		$result=$this->db->fetchAll($query);
		//error_log($this->db->getProfiler()->getLastQueryProfile()->getQuery());
		return $result;
	}

	/*
array(
	2=>array(
		'name'=>'abc',
		'slug_name' => 'leisure_55675',
		'level' => 1,
		'sub_cat'=>array(
			array(
				'name'=>'abc1',
				'level' => 2,
				'slug_name'=>'sightseeing_91669',
				'sub_cat'=>array(
					122 => array(
						'name'=>'abcd1',
						..
	 */
	public function getAll(){
		$query = $this->db->select()
			->from('item',array('id','name','slug_name','category_ids'))
			->where('type=?','category_tag');
		$result=$this->db->fetchAll($query);

		$cat = array();
		foreach($result as &$value){
			if ($value['id']==1) continue;
			$subject  = $value['category_ids'];
			$pattern  = '/\|(?P<ids>\d+)/';
			preg_match_all($pattern, $subject, $matches);
			$value['level']=count($matches['ids']);

			array_shift($matches['ids']); // ignore |1|


			if ($value['level'] == 3){
				//3rd level category
				$cat[$matches['ids'][0]]['sub_cat'][$matches['ids'][1]]['sub_cat'][$value['id']]['name'] = $value['name'];
				$cat[$matches['ids'][0]]['sub_cat'][$matches['ids'][1]]['sub_cat'][$value['id']]['slug_name'] = $value['slug_name'];
				$cat[$matches['ids'][0]]['sub_cat'][$matches['ids'][1]]['sub_cat'][$value['id']]['level'] = $value['level'];
			}else if ($value['level'] == 2){
				//2nd level category
				$cat[$matches['ids'][0]]['sub_cat'][$value['id']]['name'] = $value['name'];
				$cat[$matches['ids'][0]]['sub_cat'][$value['id']]['slug_name'] = $value['slug_name'];
				$cat[$matches['ids'][0]]['sub_cat'][$value['id']]['level'] = $value['level'];
			}else{
				//top level category
				$cat[$value['id']]['name'] = $value['name'];
				$cat[$value['id']]['slug_name'] = $value['slug_name'];
				$cat[$value['id']]['level'] = $value['level'];
			}
		}

		return $cat;

	}
}