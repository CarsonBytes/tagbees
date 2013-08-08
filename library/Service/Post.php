<?php
class Service_Post{
	protected $identity;
	protected $db;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
	}
	/*
	 *	$name,$description,
		$has_original_price=0,$price='',$has_discount=0,$discount_mode='',
		$discount_price='',$discount_percent='',$discount_custom='',
		$tree_ids='',
		$payment_method=array('any'),
		$time_zone_id=0,
		$begin_time='',$has_end_time=0,$end_time='',
		$place='',$lat='',$lng='',$zoom='',
		$relateds=array(),$related_types=array(),
		$imgs=array(),$img_descriptions=array()
	 *
	 */
	public function add($inputs, $prefix = 'new_event_'){
		try {
      $item_tag_table_data = array();
      
      // handle normal and special fields to be combined
      $to_be_customed = array('begin_date','end_date','begin_time','end_time', 'is_any_time', 'is_all_day', 'description');
      $customed_inputs = array();
      foreach($inputs as $key => $value){
        $name = str_replace($prefix, '', $key);
        if (in_array($name, $to_be_customed)) $customed_inputs[$name] = $value;
        else $item_tag_table_data[$name]=$value;
      }
      if ($customed_inputs['is_any_time']==0  && isset($customed_inputs['begin_date']) && isset($customed_inputs['end_date'])){
      $item_tag_table_data['begin_datetime'] = $customed_inputs['begin_date'] . ' ' . ($customed_inputs['is_all_day'] == 0 ? $customed_inputs['begin_time'] : "");
      $item_tag_table_data['end_datetime'] = $customed_inputs['end_date'] . ' ' . ($customed_inputs['is_all_day'] == 0 ? $customed_inputs['end_time'] : "");
      }
      
      // strip html text if needed
      $item_tag_table_data['description'] = $customed_inputs['description'];
      $item_tag_table_data['teaser'] = mb_substr(strip_tags(html_entity_decode($item_tag_table_data['description'])), 0, 50, 'UTF-8');

      // handling tag...
      // setting old tags
      $tag_ids=array();
      if (isset($item_tag_table_data['tag_ids'])){
        $tag_ids=$item_tag_table_data['tag_ids'];
        unset($item_tag_table_data['tag_ids']);
      }
      // adding new tags
      if (isset($item_tag_table_data['new_tags'])){
        // add new related item of the target promotion first to item
        $tagService = new Service_Tag();
        $tag_ids=array_merge(
          $tag_ids,
          $tagService->bulkAddTags(
            $item_tag_table_data['new_tags']
          )
        );
        unset($item_tag_table_data['new_tags']);
      }
      
      //adding extra fields before posting to db
      $commonService=new Common();
      $item_tag_table_data['create_time']=date('Y-m-d H:i:s');
      $item_tag_table_data['slug_name']=$commonService->slugUnique($inputs[$prefix.'name']);
      
      $this->db->insert('item',$item_tag_table_data);
      $post_id = $this->db->lastinsertid('item','id');
      
			if (!empty($tag_ids)){
				// add to item_tag
				foreach($tag_ids as $tag_id){
					$vars=array(
						'item_id'=>$post_id,
						'tag_id'=>$tag_id,
						//'type'=>$relatedItem['type'],
						'status'=>1,
						'submitter_id'=>$this->identity->item_id,
						'create_time'=>date('Y-m-d H:i:s'),
						'update_time'=>date('Y-m-d H:i:s')
					);
					$this->db->insert('item_tag',$vars);
				}
			}
			// check tmp_upload and convert to image
			/*$imageService=new Service_Image();
			$imageService->moveTmpImages($imgs,$img_descriptions,$promoId,$slugName);*/
			//$imageService->moveTmpImages(1,$promoId,$slugName)
			//return 1;
			
      $actionService=new Service_Action();
      $content = array(
          'fields' => $vars
      );
      $actionService->addAction('create', $post_id, 'event', $content);
            
			return $item_tag_table_data['slug_name'];
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	//data contain item's fields and log reason
	public function update($id,$changed_fields,$reason){
	    //adding extra fields before posting to db
	    //$commonService=new Common();
	    //if ($data['slug_name']=='')$data['slug_name']=$commonService->slugUnique($data['name']);
	    $changed_fields['update_time']=date('Y-m-d H:i:s');
	    //$changed_fields['submitter_id']=$this->identity->item_id;
	    if (isset($changed_fields['begin_datetime']))
	        $changed_fields['begin_datetime']=Common::spaceToNull($changed_fields['begin_datetime']);
	    if (isset($changed_fields['end_datetime']))
	        $changed_fields['end_datetime']=Common::spaceToNull($changed_fields['end_datetime']);
	    if (isset($changed_fields['application_begin_datetime']))
	        $changed_fields['application_begin_datetime']=Common::spaceToNull($changed_fields['application_begin_datetime']);
	    if (isset($changed_fields['application_end_datetime']))
	        $changed_fields['application_end_datetime']=Common::spaceToNull($changed_fields['application_end_datetime']);

	    try {
	    	$this->db->update('item',$changed_fields,'id='.$id);

	    	$actionService=new Service_Action();
            $content = array(
                'fields' => array_keys($changed_fields),
                'reason' => $reason
            );
	    	$actionService->addAction('update', $id, 'event', $content);

	    } catch (Exception $e) {
	    	return $e->getMessage();
	    }
	}


	/*
	 * bulk import to item table regarding specific fields
	 *
	 */
	public function bulkImport($fields,$items,$common_fields){
		error_log( ' item insertion start');
		$common=new Common();
		try {
		$j=0;
		foreach($items as $item){
			$vars=array();
			for ($i=0;$i<count($fields);$i++){
				if (isset($item[$i])){
					$vars[$fields[$i]]= $item[$i]!='' ? $item[$i] : new Zend_Db_Expr('NULL');
				}else{
				$vars[$fields[$i]]=new Zend_Db_Expr('NULL');
				}
			}
			foreach($common_fields as $key=>$value){
				$vars[$key]=$value;
			}
			if (!isset($vars['slug_name']))
				$vars['slug_name']=	$common->slugUnique($vars['name']);
			error_log( print_r($vars,1));
			$result=$this->db->insert('item',$vars);
			echo 'item#'.$this->db->lastInsertId()." '".$vars['name']."' inserted.<br />";
			error_log( '#'.$j.' item inserted');
			$j++;
		}
		return true;
		} catch (Exception $e) {
		return $e->getMessage();
		}
	}

	public function activate($id,$flag=1){
		$var=array('status'=>$flag);
		$this->db->update(
    		'item',
    		$var,
    		'id='.$id
		);
	}

	public static function validate($data,$type){
		//validation
		$options= array(
			'missingMessage' => "'%field%' is required",
			//'notEmptyMessage' => "A non-empty value is required for field '%field%'",
			'allowEmpty' => true
		);
		$filters=array(
			//'*'   => 'StringTrim'
		);
		$validators=array();

		foreach(Common::config()->post->$type->toArray() as $key=>$value){
			if (!empty($value['Zend_Validate'])){
				$validators[$key]=$value['Zend_Validate'];
			}else{
				$validators[$key]=array();
			}
			if(isset($value['default'])&&(!isset($data[$key]))){
				$data[$key]=$value['default'];
			}
			if (isset($value['Custom_Validate'])){
				$para_arr=array();
				foreach($value['Custom_Validate']['para'] as $para_name){
					if (!isset($data[$para_name]))
						$data[$para_name]='';
					$para_arr[]=$data[$para_name];
				}
				$rc=new ReflectionClass($value['Custom_Validate']['func']);
				$obj = $rc->newInstanceArgs($para_arr);
				$validators[$key][]=$obj;
			}
		}
		$input=new Zend_Filter_Input($filters, $validators,$data,$options);

		return $input;
	}
}