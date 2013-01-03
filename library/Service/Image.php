<?php
class Service_Image{
	protected $identity;
	protected $session;
	protected $config;
	protected $db;
	protected $source_path;
	protected $source_thumbnail_path;

	public $upload_path = '';
	public $upload_thumbnail_path = '';
	public $upload_url = '';
	public $upload_thumbnail_url = '';

	function __construct(){
		$this->session=Zend_Session::getId();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		if (Zend_Auth::getInstance()->hasIdentity()){
			$this->identity=Zend_Auth::getInstance()->getIdentity();
		}
		$this->upload_path = Zend_Registry::get('config')->upload_paths->event->pic ;
        $this->upload_thumbnail_path = $this->upload_path .'/thumbnail/';
        $this->upload_url = Common::changePathToURL($this->upload_path);
        $this->upload_thumbnail_url = Common::changePathToURL($this->upload_thumbnail_path);
	}

	/*	waiting for reconstructure
	 * public function upload($params) {
	        $adapter = new Zend_File_Transfer_Adapter_Http();

	        if (!file_exists($this->upload_path)) mkdir($this->upload_path, 0777,true);
	        if (!file_exists($this->upload_thumbnail_path)) mkdir($this->upload_thumbnail_path, 0777,true);

	        $adapter->setDestination($this->upload_path);
	        $adapter->addValidator('Extension', false, 'jpg,png,gif');

	        $files = $adapter->getFileInfo();
	        foreach ($files as $file => $info) {
				$parts=pathinfo($adapter->getFileName($file));
				$rename=md5(time().$parts['filename']).'.'.$parts['extension'];
				$i=0;
				while (file_exists($this->upload_path.'/'.$rename)){
					$rename=md5(time().strval($i).$parts['filename']).'.'.$parts['extension'];
					$i++;
				}
				// file uploaded & is valid
				//if (!$adapter->isUploaded($file)) continue;
				//if (!$adapter->isValid($file)) continue;

				$adapter->addFilter('Rename', $this->upload_path.'/'.$rename);
				// receive the files into the user directory
				$adapter->receive($file); // this has to be on top

				$imageService= new Service_Image();
				$imageService->save($this->identity->item_id,$params->item_id,$rename);
				if (true !== ($pic_error = $imageService->resize($this->upload_path.'/'.$rename, $this->upload_thumbnail_path.'/'.$rename, 100, 100))) {
				  echo $pic_error;
				  //unlink($pic_name);
				}

				$fileclass = new stdClass();
				// we stripped out the image thumbnail for our purpose, primarily for security reasons
				// you could add it back in here.
				$fileclass->name = $rename;

				$fileclass->size = $adapter->getFileSize($file);

				$fileclass->type = $adapter->getMimeType($file);
				$fileclass->delete_url = Zend_Controller_Front::getInstance()->getBaseUrl().'upload?file='.$rename;
				$fileclass->delete_type = 'DELETE';
				//$fileclass->error = 'null';
				$fileclass->thumbnail_url = $this->upload_thumbnail_url.'/'.$rename;
				$fileclass->url = $this->upload_url.'/'.$rename;

				$datas[] = $fileclass;
	        }
	        return $datas;

	}
	 */
	 
	public function upload($params) {
        $adapter = new Zend_File_Transfer_Adapter_Http();

        if (!file_exists($this->upload_path)) mkdir($this->upload_path, 0777,true);
        if (!file_exists($this->upload_thumbnail_path)) mkdir($this->upload_thumbnail_path, 0777,true);

        $adapter->setDestination($this->upload_path);
        $adapter->addValidator('Extension', false, 'jpg,png,gif');

        $files = $adapter->getFileInfo();
        foreach ($files as $file => $info) {
			$parts=pathinfo($adapter->getFileName($file));
			$rename=md5(time().$parts['filename']).'.'.$parts['extension'];
			$i=0;
			while (file_exists($this->upload_path.'/'.$rename)){
				$rename=md5(time().strval($i).$parts['filename']).'.'.$parts['extension'];
				$i++;
			}
			// file uploaded & is valid
			//if (!$adapter->isUploaded($file)) continue;
			//if (!$adapter->isValid($file)) continue;

			$adapter->addFilter('Rename', $this->upload_path.'/'.$rename);
			// receive the files into the user directory
			$adapter->receive($file); // this has to be on top

			$imageService= new Service_Image();
			/* 
			$imageService->save($this->identity->item_id,$params->item_id,$rename);
			 */
			if (true !== ($pic_error = $imageService->resize($this->upload_path.'/'.$rename, $this->upload_thumbnail_path.'/'.$rename, 100, 100))) {
			  echo $pic_error;
			  //unlink($pic_name);
			}

			$fileclass = new stdClass();
			// we stripped out the image thumbnail for our purpose, primarily for security reasons
			// you could add it back in here.
			$fileclass->name = $rename;

			$fileclass->size = $adapter->getFileSize($file);

			$fileclass->type = $adapter->getMimeType($file);
			$fileclass->delete_url = Zend_Controller_Front::getInstance()->getBaseUrl().'upload?file='.$rename;
			$fileclass->delete_type = 'DELETE';
			//$fileclass->error = 'null';
			$fileclass->thumbnail_url = $this->upload_thumbnail_url.'/'.$rename;
			$fileclass->url = $this->upload_url.'/'.$rename;

			$datas[] = $fileclass;
        }
        return $datas;

	}
	 
	

	public function delete($file_name) {

	      // this has been customized to remove only specific images in certain user_id folders
	      // you should modify that to your needs
	      $file_path = $this->upload_path.'/'.$file_name;
	      $file_thumbnail_path = $this->upload_thumbnail_path.'/'.$file_name;
	      //$success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
	      $success1=unlink($file_path);
	      $success2=unlink($file_thumbnail_path);
	    $this->_helper->json($success1 && $success2);
	}

	/*
	 * $code	array|string
	 */
	public function removeTmpImages($code){
		$search_path_segment=array("[username]","[session_id]");
		$replace_path_segment=array($this->identity->username,$this->session);

		$this->source_path = str_replace($search_path_segment,$replace_path_segment,$this->config["uploader"]["tmp"]["picture"]["destination"]);
		$this->source_thumbnail_path = str_replace($search_path_segment,$replace_path_segment,$this->config["uploader"]["tmp"]["thumbnail"]["destination"]);

		//return array($this->session,$this->source_path,$this->source_thumbnail_path);

		if (!is_array($code)){
			return $this->removeTmpImage($code);
		}else{
			foreach($code as $value){
				$status=$this->removeTmpImage($value);
			}
			return 'success';
		}
	}
	/*
	 * $code	string
	 */
	public function removeTmpImage($code){
		if (file_exists($this->source_path . $code)) {
			unlink($this->source_path . $code);
		}else{
			return ($this->source_path . $code . " does not exist");
		}
		if (file_exists($this->source_thumbnail_path . $code)) {
			unlink($this->source_thumbnail_path . $code);
		}else{
			return ($this->source_thumbnail_path . $code . " does not exist");
		}
	    $data=array(
	    	"status"=>2
	    );
	    $where['path = ?']=$this->source_path . $code;
	    $where['session_id = ?']=$this->session;
	    $update=$this->db->update("tmp_upload",$data,$where);
	}

	/*
	 * save image infos
	 *
	 */
	public function save($user_id, $item_id, $filename, $title='',$description='', $is_profile_pic=0){
		$vars=array(
			'user_id'=>$user_id,
			'item_id'=>$item_id,
			'filename'=>$filename,
			'title'=>$title,
			'description'=>$description,
			'is_profile_pic'=>$is_profile_pic,
			'create_time'=>date('Y-m-d H:i:s')
		);
		$this->db->insert('image',$vars);
	}

	/*
	 *
	 */
	/*
	public function getImages($item_ids){
			$select=$this->db->select()
				->from('image');
			if (is_array($item_ids)){
				$select->where('item_id in (?)',$item_ids);
			}else{
				$select->where('item_id=?',$item_ids);
			}
			$result = $this->db->fetchAll($select);
	
			$packed=array();
			foreach($result as $value){
				if (!isset($packed[$value['item_id']])){
					$packed[$value['item_id']]=array();
				}
				$packed[$value['item_id']][]=array(
					'img_title'=>$value['title'],
					'img_description'=>$value['description'],
					'img_code'=>$value['code'],
					'img_is_profile_pic'=>$value['is_profile_pic'],
					'img_user_id'=>$value['user_id']
				);
			}
			return $packed;
		}*/
	
	/*
	 *
	 */
	public function getPic($item_ids){
		$select=$this->db->select()
			->from('image');
		if (is_array($item_ids)){
			foreach($item_ids as $item_id){
				$select->where('item_pic_id=?',$item_id);
			}
		}else{
			$select->where('item_pic_id=?',$item_ids);
		}
		return $this->db->fetchAll($select);
	}

	function resize($source_image, $destination, $tn_w, $tn_h, $quality = 100, $wmsource = false)
	{
	    $info = getimagesize($source_image);
	    $imgtype = image_type_to_mime_type($info[2]);

	    #assuming the mime type is correct
	    switch ($imgtype) {
	        case 'image/jpeg':
	            $source = imagecreatefromjpeg($source_image);
	            break;
	        case 'image/gif':
	            $source = imagecreatefromgif($source_image);
	            break;
	        case 'image/png':
	            $source = imagecreatefrompng($source_image);
	            break;
	        default:
	            die('Invalid image type.');
	    }

	    #Figure out the dimensions of the image and the dimensions of the desired thumbnail
	    $src_w = imagesx($source);
	    $src_h = imagesy($source);


	    #Do some math to figure out which way we'll need to crop the image
	    #to get it proportional to the new size, then crop or adjust as needed

	    $x_ratio = $tn_w / $src_w;
	    $y_ratio = $tn_h / $src_h;

	    if (($src_w <= $tn_w) && ($src_h <= $tn_h)) {
	        $new_w = $src_w;
	        $new_h = $src_h;
	    } elseif (($x_ratio * $src_h) < $tn_h) {
	        $new_h = ceil($x_ratio * $src_h);
	        $new_w = $tn_w;
	    } else {
	        $new_w = ceil($y_ratio * $src_w);
	        $new_h = $tn_h;
	    }

	    $newpic = imagecreatetruecolor(round($new_w), round($new_h));
	    imagecopyresampled($newpic, $source, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
	    $final = imagecreatetruecolor($tn_w, $tn_h);
	    $backgroundColor = imagecolorallocate($final, 255, 255, 255);
	    imagefill($final, 0, 0, $backgroundColor);
	    //imagecopyresampled($final, $newpic, 0, 0, ($x_mid - ($tn_w / 2)), ($y_mid - ($tn_h / 2)), $tn_w, $tn_h, $tn_w, $tn_h);
	    imagecopy($final, $newpic, (($tn_w - $new_w)/ 2), (($tn_h - $new_h) / 2), 0, 0, $new_w, $new_h);

	    #if we need to add a watermark
	    if ($wmsource) {
	        #find out what type of image the watermark is
	        $info    = getimagesize($wmsource);
	        $imgtype = image_type_to_mime_type($info[2]);

	        #assuming the mime type is correct
	        switch ($imgtype) {
	            case 'image/jpeg':
	                $watermark = imagecreatefromjpeg($wmsource);
	                break;
	            case 'image/gif':
	                $watermark = imagecreatefromgif($wmsource);
	                break;
	            case 'image/png':
	                $watermark = imagecreatefrompng($wmsource);
	                break;
	            default:
	                die('Invalid watermark type.');
	        }

	        #if we're adding a watermark, figure out the size of the watermark
	        #and then place the watermark image on the bottom right of the image
	        $wm_w = imagesx($watermark);
	        $wm_h = imagesy($watermark);
	        imagecopy($final, $watermark, $tn_w - $wm_w, $tn_h - $wm_h, 0, 0, $tn_w, $tn_h);

	    }
	    if (imagejpeg($final, $destination, $quality)) {
	        return true;
	    }
	    return false;
	}


	/*public function getJoinQuery($select,$linked_item_id='',$is_profile_pic=true){
		$nested=$this->db->select()
			->from('image',array(
			            	'img_id'=>'id',
			            	'img_title'=>'title',
			            	'img_item_id'=>'item_id',
			            	'img_description'=>'description',
			            	'img_code'=>'code',
			            	'img_is_profile_pic'=>'is_profile_pic',
			            	'img_user_id'=>'user_id'
						)
			);
		if ($is_profile_pic){
			$nested->where('is_profile_pic=?',$is_profile_pic);
		}
		return $select->joinLeft(
				array('image_self'=>$nested),
				'image_self.img_item_id='.$linked_item_id
			);
	}*/
	
    public function getJoinQuery($select, $linked_item_id='', $is_main_pic_only=0){
         $select->joinLeft(
                    array('img'=>'image'),
                    'img.item_id='.$linked_item_id,
                    array(
                        'img_titles'=> new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.title,''))"),
                        'img_descriptions'=> new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.description,''))"),
                        'img_positions'=> new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.position,''))"),
                        'img_filenames'=> new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.filename,''))"),
                        'img_is_main_pics'=> new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.is_main_pic,''))")                       
                    )
                )
                ->group($linked_item_id);
                //->order('img_positions');
        if ($is_main_pic_only == 1){
            $select->where('img.is_main_pic = 1');
        };
        return $select;
    }
}