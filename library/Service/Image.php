<?php
class Service_Image {
  protected $identity;
  protected $config;
  protected $path;
  protected $thumbnail_dir = '/thumbnail';

  function __construct() {
    if (Zend_Auth::getInstance() -> hasIdentity()) {
      $this -> identity = Zend_Auth::getInstance() -> getIdentity();
    } /*else{
      return false;
    }*/
    $this->path = Zend_Registry::get('config_ini') -> upload -> imagePath;
    $this->db = Zend_Db_Table::getDefaultAdapter();
  }

  public function upload($item_id) {
    $adapter = new Zend_File_Transfer_Adapter_Http();

    if (!file_exists($this->path))
      mkdir($this->path, 0777, true);
    if (!file_exists($this->path.$this->thumbnail_dir))
      mkdir($this->path.$this->thumbnail_dir, 0777, true);

    $adapter -> setDestination($this->path);
    $adapter -> addValidator('Extension', false, 'jpeg,jpg,png,gif');

    $files = $adapter -> getFileInfo();
    
    //preparing to transfer the uploaded response back
    $datas = array();
    $datas['files'] = array();
    foreach ($files as $file => $info) {
      $parts = pathinfo($adapter -> getFileName($file));
      $rename = Common::generateRandomString(). '.' . $parts['extension'];
      //md5(time() . $parts['filename']) . '.' . $parts['extension'];
      while (file_exists($this->path . '/' . $rename)) {
        $rename = Common::generateRandomString(). '.' . $parts['extension'];
      }
      // file uploaded & is valid
      if (!$adapter->isUploaded($file)) continue;
      if (!$adapter->isValid($file)) continue;

      $adapter -> addFilter('Rename', $this->path . '/' . $rename);
      // receive the files into the user directory
      $adapter -> receive($file);
      
      if (true !== ($pic_error = $this->resize($this->path . '/' . $rename, $this->path.$this->thumbnail_dir . '/' . $rename, 100, 100))) {
        //echo $pic_error;
        //delete the last uploaded image
        unlink($this->path . '/' . $rename);
        echo '<pre>';var_dump('variable');echo '</pre>';
        continue;
      }
        
      if (!$this->add($item_id,$rename)){
        echo '<pre>';var_dump('variable');echo '</pre>';
        continue;
      };
       
      $request = Zend_Controller_Front::getInstance()->getRequest();
      $hostUrl = $request->getScheme() . '://' .$request->getHttpHost();
      
      $datas['files'][]=array(
        'deleteType'=> "DELETE",
        'deleteUrl'=> $this->getDeleteUrl($rename),
        'name'=> $parts['filename'],
        'size'=> $adapter -> getFileSize($file),
        'type'=> $adapter->getMimeType($file),
        'thumbnailUrl'=>$this->getThumbnailUrl($rename),
        'url'=> $this->getImageUrl($rename),
      );
    }
    return $datas;

  }

  /*
   * delete image and infos from db
   *
   */
  public function delete($filename, $type = 'event') {
    $response = array();
    $response[$filename] = unlink($this->path . '/' . $filename) && unlink($this->path . $this->thumbnail_dir.'/' . $filename);
    
    $this->db->delete('image', array(
      'filename = ?' => $filename
    ));
    
    return $response;
  }
  
  /*
   * get image infos from db
   */
  public function getDbInfos($item_id, $type='event') {
    $select=$this->db->select()
      ->from('image')
      ->where ('item_id = ?',$item_id);
    $result=$this->db->fetchAll($select);
    
    $datas = array();
    $datas['files'] = array();
    foreach ($result as $row){
      if(file_exists($this->path . '/' . $row['filename'])){
        $datas['files'][]=array(
          'deleteType'=> "DELETE",
          'name'=> $row['filename'],
          'size'=> $this->getFileSize($this->path.'/'.$row['filename']),
          'type'=> $this->getFileType($this->path.'/'.$row['filename']),
          'deleteUrl'=> $this->getDeleteUrl($row['filename']),
          'thumbnailUrl'=>$this->getThumbnailUrl($row['filename']),
          'url'=> $this->getImageUrl($row['filename']),
          'is_main_pic'=> $row['is_main_pic'],
          'position'=> $row['position'],
          'caption'=> $row['caption']
        );
      } else {
        $this->db->delete('image', array(
          'filename = ?' => $row['filename']
        ));
      }
    }
    return $datas;
  }

  protected function getDeleteUrl($filename='',$type='event') {
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $hostUrl = $request->getScheme() . '://' .$request->getHttpHost();
    return $hostUrl.Zend_Controller_Front::getInstance() -> getBaseUrl()."/iframe/event/img_handle?filename=".$filename;
  }

  protected function getThumbnailUrl($filename='',$type='event') {
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $hostUrl = $request->getScheme() . '://' .$request->getHttpHost();
    return $hostUrl.Common::changePathToURL($this->path.$this->thumbnail_dir.'/'.$filename);
  }

  protected function getImageUrl($filename='',$type='event') {
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $hostUrl = $request->getScheme() . '://' .$request->getHttpHost();
    return $hostUrl.Common::changePathToURL($this->path.'/'.$filename);
  }
   
  protected function getFileType($file_path) {
      switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
          case 'jpeg':
          case 'jpg':
              return 'image/jpeg';
          case 'png':
              return 'image/png';
          case 'gif':
              return 'image/gif';
          default:
              return '';
      }
  }

  protected function getFileSize($file_path, $clear_stat_cache = false)    
  {
      if ($clear_stat_cache) {
          if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
              clearstatcache(true, $file_path);
          } else {
              clearstatcache();
          }
      }
      return $this->fixIntegerOverflow(filesize($file_path));
  }
    
  // Fix for overflowing signed 32 bit integers,
  // works for sizes up to 2^32-1 bytes (4 GiB - 1):
  protected function fixIntegerOverflow($size) {
      if ($size < 0) {
          $size += 2.0 * (PHP_INT_MAX + 1);
      }
      return $size;
  }
  /*
   * add thumbnail and image infos to do
   *
   */
  public function add($item_id, $filename, $user_id=null, $caption = '', $is_main_pic = 0) {
    
    if ($user_id == null && Zend_Auth::getInstance() -> hasIdentity()) {
      $user_id = Zend_Auth::getInstance() -> getIdentity() ->item_id;
    }
    
    if ($user_id != null){
      $vars = array(
        'user_id' => $user_id, 
        'item_id' => $item_id, 
        'filename' => $filename, 
        'caption' => $caption, 
        'is_main_pic' => $is_main_pic, 
        'create_time' => date('Y-m-d H:i:s')
      );
      
      $this -> db -> insert('image', $vars);
      return true;
    } else {
      /*return false;*/
    }
    
    //demo
    $vars = array(
      'user_id' => 5, 
      'item_id' => $item_id, 
      'filename' => $filename, 
      'caption' => $caption, 
      'is_main_pic' => $is_main_pic, 
      'create_time' => date('Y-m-d H:i:s')
    );
    $this -> db -> insert('image', $vars);
      return true;
  }

  //resize and save to thumbnail
  function resize($source_image, $destination, $tn_w, $tn_h, $quality = 100, $wmsource = false) {
    $info = getimagesize($source_image);
    $imgtype = image_type_to_mime_type($info[2]);

    #assuming the mime type is correct
    switch ($imgtype) {
      case 'image/jpeg' :
        $source = imagecreatefromjpeg($source_image);
        break;
      case 'image/gif' :
        $source = imagecreatefromgif($source_image);
        break;
      case 'image/png' :
        $source = imagecreatefrompng($source_image);
        break;
      default :
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
    imagecopy($final, $newpic, (($tn_w - $new_w) / 2), (($tn_h - $new_h) / 2), 0, 0, $new_w, $new_h);

    #if we need to add a watermark
    if ($wmsource) {
      #find out what type of image the watermark is
      $info = getimagesize($wmsource);
      $imgtype = image_type_to_mime_type($info[2]);

      #assuming the mime type is correct
      switch ($imgtype) {
        case 'image/jpeg' :
          $watermark = imagecreatefromjpeg($wmsource);
          break;
        case 'image/gif' :
          $watermark = imagecreatefromgif($wmsource);
          break;
        case 'image/png' :
          $watermark = imagecreatefrompng($wmsource);
          break;
        default :
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

  public function getJoinQuery($select, $linked_item_id = '', $is_main_pic_only = 0) {
    $select -> joinLeft(array('img' => 'image'), 'img.item_id=' . $linked_item_id, array('img_titles' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.title,''))"), 'img_descriptions' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.description,''))"), 'img_positions' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.position,''))"), 'img_filenames' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.filename,''))"), 'img_is_main_pics' => new Zend_Db_Expr("GROUP_CONCAT(ifnull(img.is_main_pic,''))"))) -> group($linked_item_id);
    //->order('img_positions');
    if ($is_main_pic_only == 1) {
      $select -> where('img.is_main_pic = 1');
    };
    return $select;
  }

}
