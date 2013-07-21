<?php
class Common{
	protected $identity;
	function __construct(){
		$this->identity=Zend_Auth::getInstance()->getIdentity();
		$this->db = Zend_Db_Table::getDefaultAdapter();
	}

	/*public function getLastVisitedUrl(){
	    $controller= Zend_Controller_Front::getInstance();
	    if (!isset(Common::getSession()->last_visited_url)) Common::getSession()->last_visited_url=$controller->getRequest()->REQUEST_URI;
	    if (
	    		(strpos($controller->getRequest()->getRequestUri(),'auth/login')!==false)||
	    		(strpos($controller->getRequest()->getRequestUri(),'auth/signup')!==false)
	    )
	    {
	    	Common::getSession()->last_visited_url=$controller->getRequest()->getParam('redirect');
	    }
	    //return $controller->getRequest()->REQUEST_URI;
	    return Common::getSession()->last_visited_url;
	}*/

	public static function config(){
		return new Zend_Config(require APPLICATION_PATH . '/configs/config.php');
	}

	//mainly for datetime mysql input
	public static function spaceToNull($data){
	    return $data=='' ? null : $data;
	}

	//change file path to web path
	public static function changePathToURL($file_path){
		return Zend_Controller_Front::getInstance()->getBaseUrl().str_replace(array('APPLICATION_PATH "/../public/',APPLICATION_PATH. "/../public/",'//'),array('"','/','/'),$file_path);
	}

	public static function phpMysqlQuery($sql){
		$db = mysql_connect(
			Zend_Registry::get('config_ini')->resources->db->params->host,
			Zend_Registry::get('config_ini')->resources->db->params->username,
			Zend_Registry::get('config_ini')->resources->db->params->password
		) or die("Database error");
	    mysql_select_db(Zend_Registry::get('config_ini')->resources->db->params->dbname, $db);
	    $all = array();
	    $result=mysql_query($sql);
	    //return mysql_num_rows($result);
	    while ($all[] = mysql_fetch_assoc($result)) {
	    }
	    return $all;
	}

	public function insertReplace($table,$where,$values){
		$select=$this->db->select()
				->from(array($table));
		foreach ($where as $key => $value){
				$select->where($key,$value);
		}

		$values['update_time']=date('Y-m-d H:i:s');
		if ($this->db->fetchRow($select)){
			$where2=array();
			foreach ($where as $key => $value){
				$where2[]=$this->db->quoteInto($key, $value);
			}
			$this->db->update($table,$values,$where2);
		}else{
			$values['create_time']=date('Y-m-d H:i:s');
			$this->db->insert($table,$values);
		}

		return true;
	}

	//there is still a chance that slug title with random number may collide!!
	public function slugUnique($string,$type='')
	{
	    $slug_title=$this->getSlug($string);

	    $select=$this->db->select()
				->from ('item', array('count(*)'))
				->where ('slug_name = ?',$slug_title);
		if ($type!=='') $select->where ('type = ?',$type);
		while($this->db->fetchOne($select)!=0){
			$slug_title.='-'.mt_rand(0,100000);
			$select=$this->db->select()
				->from ('item', array('count(*)'))
				->where ('slug_name = ?',$slug_title);
		}
		return $slug_title;
	}

	public static function getSlug($name){
		//return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
		return str_replace(array(' ','　'),'-',$name);
	}

	public static function getName($slug){
		//return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
		return ucwords(str_replace(array('-'),' ',$string));
	}


	public static function getImageUrl($filename, $type=''){
		if ($filename=='') $path=Zend_Registry::get('config_ini')->uploader->uploads->no_profile_pic;
		else $path=Zend_Registry::get('config_ini')->uploader->uploads->destination.$type.'/'.$filename;
		return Common::changePathToURL($path);
	}

	public function splitLatitude($string){
		preg_match('/\((.+),(.+)\)/',$string,$result);
		return $result;
	}

	/* These are no longer in use as we have already shift to a simplified db table format which do sorting, filtering through mysql
	public function datetime_sort($a, $b)
	{
	   return(strtotime($b['create_time']) - strtotime($a['create_time']));
	}

	public function interest_cnt_sort($a, $b)
	{
	   return($b['interest_cnt'] - $a['interest_cnt']);
	}
	public function score_sort($a, $b)
	{
	   return strcmp($b['score'],$a['score']);
	}
	*/
    public function arrayToObject($array) {
		if(!is_array($array)) {
			return $array;
		}
		$object = new stdClass();
		if (is_array($array) && count($array) > 0) {
		  foreach ($array as $name=>$value) {
		     $name = strtolower(trim($name));
		     if (!empty($name)) {
		        $object->$name = $this->arrayToObject($value);
		     }
		  }
	      return $object;
		}
	    else {
	      return FALSE;
	    }
	}
	public function preg_grep_keys( $pattern, $input)
	{
	    $keys = preg_grep( $pattern, array_keys( $input ));
	    //print_r(array_keys( $input ));
	    //print_r($keys);
	    $vals = array();
	    foreach ( $keys as $key )
	    {
	        $vals[$key] = $input[$key];
	    }
	    return $vals;
	}

	public function rrmdir($dir) {
	   if (is_dir($dir)) {
	     $objects = scandir($dir);
	     foreach ($objects as $object) {
	       if ($object != "." && $object != "..") {
	         if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
	       }
	     }
	     reset($objects);
	     rmdir($dir);
	   }
	}
	/*
	public function remoteUrl($url){
		if (function_exists('curl_init')) {
		   // initialize a new curl resource
		   $ch = curl_init();

		   curl_setopt($ch, CURLOPT_COOKIESESSION, false);
		   //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
		   curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

		   // set the url to fetch
		   curl_setopt($ch, CURLOPT_URL, 'http://maps.googleapis.com/maps/api/geocode/json?latlng=40.714224,-73.961452&sensor=false');

		   // don't give me the headers just the content
		   curl_setopt($ch, CURLOPT_HEADER, 0);

		   // return the value instead of printing the response to browser
		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		   // use a user agent to mimic a browser
		   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

		   return curl_exec($ch);

		   // remember to always close the session and free all resources
		   curl_close($ch);
		} else {
		  return false;
		   // curl library is not installed so we better use something else
		}
	}*/
	public function saveLog($module,$controller,$action,$params){
		/*if (($_SERVER['REMOTE_ADDR']=='95.88.137.172')||($_SERVER['REMOTE_ADDR']=='127.0.0.1')){
			return;
		}
		//return;
		*/
		$paramsTxt=array();
		$i=0;
		//print_r($params);
		foreach($params as $key=>$value){
			if (is_string($value)){
				if($value!=''){
					$paramsTxt[$key]=urlencode($value);
				}
			}
		}
		if ($module==null) $module='default';
		$vars=array(
				'module'=>$module,
				'controller'=>$controller,
				'action'=>$action,
				'params'=>urldecode(json_encode($paramsTxt)),
				'ip'=>$_SERVER['REMOTE_ADDR'],
				'agent'=>$_SERVER['HTTP_USER_AGENT'],
				'create_time'=>date('Y-m-d H:i:s')
			);
		if (Zend_Auth::getInstance()->hasIdentity()){
			$vars['user_id']=$this->identity->item_id;
		}
		$this->db = Zend_Db_Table::getDefaultAdapter();
		$this->db->insert('log_page',$vars);
	}

	public function outDbConnect(){
		$ini = parse_ini_file(realpath(dirname(__FILE__) . "../../application/configs/application.ini"));
		$conn = mysql_connect( $ini["resources.db.params.host"], $ini["resources.db.params.username"], $ini["resources.db.params.password"]) or die;
		if (!$conn)
		{
		  die('Could not connect: ' . mysql_error());
		}
		mysql_select_db($ini["resources.db.params.dbname"]);
	}

	public function addPrefixToArrayValues($prefix,$array){
		foreach($array as &$value){
			$value=$prefix.$value;
		}
		return $array;
	}

	/*
	 * http://www.datatables.net/examples/
	 */
	/* $aColumns= Array of database columns which should be read and sent back to DataTables. Use a space where
		 * you want to insert a non-database field (for example a counter or static image)
		 */
	/* $sIndexColumn=Indexed column (used for fast and accurate table cardinality) */
	/* $sTable=DB table to use */
	public function getDataTableServerSideProcessing($aColumns,$sIndexColumn,$sTable,$get,$whereClause=array()){
	    $this->db = Zend_Db_Table::getDefaultAdapter();
		/* pack select columns*/
		$selectCols=array();
		$i=0;
		foreach($aColumns as $aColumn){
			$selectCols[]= ($i==0) ? new Zend_Db_Expr('SQL_CALC_FOUND_ROWS '.$aColumn) : $aColumn;
			$i++;
		}
		$sQuery=$this->db->select()
			->from($sTable,$selectCols);

		/* where clause*/
		foreach($whereClause as $key=>$value){
			$sQuery->where($value);
		}

		/*
		 * Paging
		 */
		if ( isset( $get['iDisplayStart'] ) && $get['iDisplayLength'] != '-1' )
		{
			$sQuery->limit($get['iDisplayLength'],$get['iDisplayStart']);
		}

		/*
		 * Ordering
		 */
		$sOrder='';
		if ( isset( $get['iSortCol_0'] ) )
		{
			for ( $i=0 ; $i<intval( $get['iSortingCols'] ) ; $i++ )
			{
				if ( $get[ 'bSortable_'.intval($get['iSortCol_'.$i]) ] == "true" )
				{
					$sOrder .= $aColumns[ intval( $get['iSortCol_'.$i] ) ]."
					 	".( $get['sSortDir_'.$i] ) .", ";
				}
			}

			$sOrder = substr_replace( $sOrder, "", -2 );
			$sQuery->order($sOrder);
		}

		/*
		 * Filtering
		 * NOTE this does not match the built-in DataTables filtering which does it
		 * word by word on any field. It's possible to do here, but concerned about efficiency
		 * on very large tables, and MySQL's regex functionality is very limited
		 */
		$sWhere = "";
		if ( $get['sSearch'] != "" )
		{
			for ( $i=0 ; $i<count($aColumns) ; $i++ )
			{
				if ($i==0)
					$sQuery->where('('.$aColumns[$i]." LIKE '%".( $get['sSearch'] )."%'");
				else if ($i==count($aColumns)-1)
					$sQuery->orWhere($aColumns[$i]." LIKE '%".( $get['sSearch'] )."%')");
				else
					$sQuery->orWhere($aColumns[$i]." LIKE '%".( $get['sSearch'] )."%'");

			}
		}

		/* Individual column filtering */
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( $get['bSearchable_'.$i] == "true" && $get['sSearch_'.$i] != '' )
			{
				$sQuery->where($aColumns[$i]." LIKE '%".($get['sSearch_'.$i])."%' ");
			}
		}

		/*
		 * SQL queries
		 * Get data to display
		 */
		$output["aaData"]=array(); //Output["aaData"]
		error_log($sQuery);
		$rResult=$this->db->query($sQuery);
		while ( $aRow = $rResult->fetch())
		{
			$row = array();
			for ( $i=0 ; $i<count($aColumns) ; $i++ )
			{
				if ( $aColumns[$i] == "version" )
				{
					/* Special output formatting for 'version' column */
					$row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
				}
				else if ( $aColumns[$i] != ' ' )
				{
					/* General output */
					$row[] = $aRow[ $aColumns[$i] ];
				}
			}
			$output['aaData'][] = $row;
		}

		/* Data set length after filtering */
		$sQuery2= $this->db->select()
			->from(null,'FOUND_ROWS()');
		$iFilteredTotal=$this->db->fetchOne($sQuery2);

		/* Total data set length */
		$sQuery3= $this->db->select()
			->from($sTable,"COUNT(".$sIndexColumn.")");
		$iTotal=$this->db->fetchOne($sQuery3);

		/*
		 * Output
		 */
		$output["sEcho"]=intval($get['sEcho']);
		$output["iTotalRecords"]=$iTotal;
		$output["iTotalDisplayRecords"]=$iFilteredTotal;
		return $output;
	}

	public static function filterEmpty($var){
		return ($var!='' || $var!=null);
	}

	public function combineDateTime(&$date,&$time){
		if (isset($date)){
			$begin_time=isset($time) ? ' '.$time : '';
			$datetime=$date.$begin_time;
		}else{
			$datetime = '';
		}
		unset($date,$time);
		return $datetime;
	}
	/*
	 * return api client
	 */
	public static function getGoogleApiClient(){
	    require_once 'Google/apiClient.php';
	    require_once 'Google/contrib/apiCalendarService.php';

	    $client = new apiClient();
	    $client->setApplicationName("Google Calendar PHP Starter Application");

	    // Visit https://code.google.com/apis/console?api=calendar to generate your
	    // client id, client secret, and to register your redirect uri.
	    // $client->setClientId('insert_your_oauth2_client_id');
	    // $client->setClientSecret('insert_your_oauth2_client_secret');
	    // $client->setRedirectUri('insert_your_oauth2_redirect_uri');
	    // $client->setDeveloperKey('insert_your_developer_key');
	    $client->setClientId('589286101955.apps.googleusercontent.com');
	    $client->setClientSecret('v0qzurFCArpI8UalzhdL_6B3');
	    $client->setRedirectUri('http://127.0.0.1/trunk/admin/test/google/oauth2callback');
	    $client->setDeveloperKey('https://127.0.0.1/');
	    if (isset($_GET['logout'])) {
	    	unset(Common::getSession()->token);
	    }

	    if (isset($_GET['code'])) {
	    	$client->authenticate();
	    	Common::getSession()->token = $client->getAccessToken();
	    	header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
	    }

	    if (isset(Common::getSession()->token)) {
	    	$client->setAccessToken(Common::getSession()->token);
	    }

	    if ($client->getAccessToken()) {
	        Common::getSession()->token = $client->getAccessToken();
	    	return $client;
	    }else {
        	$authUrl = $client->createAuthUrl();
        	print "<a class='login' href='$authUrl'>Connect Me!</a>";
        	exit();
        }
	}

	/*public static function nl2p($string){
		return str_replace("\n", "</p>\n<p>", '<p>'.$string.'</p>');
	}*/
    
    public static function getSiteDisplayLang(){
        
        // to be revamped, currently we just support chinese and english....
        if (isset(Common::getSession()->user->display_lang) && strncmp(Common::getSession()->user->display_lang, 'zh', 2) == 0) {
            $display_lang = 'zh-hk'; // for loginned users choosing chinese before
        } else if (isset(Common::getSession()->display_lang) && strncmp(Common::getSession()->display_lang, 'zh', 2) == 0) {
            $display_lang = 'zh-hk'; // for not loginned users choosing chinese before
        } else{
            $display_lang = 'en'; // default language
        }
        return $display_lang;
    }
    
    /*
     * check if file exists, then use the file path with config, else use general image
     * @para
     * placeholder type can be no_profile_pic, not_found_pic, event_default_thumbnail
     */
    public static function getUploadedImageUrl( $image_filename = NULL, $placeholder_type = 'event'){
        if ($image_filename != NULL) {
            $image_upload_path = Zend_Registry::get('config')->upload_paths->$placeholder_type->pic . $image_filename;
            if (file_exists($image_upload_path)) {
                //echo "Die Datei $filename existiert";
                return Common::changePathToURL($image_upload_path); 
            }
        }
        return  Common::changePathToURL(Zend_Registry::get('config')->pics->sys->not_found);
    }

    public static function setCookie($key, $value){
        //$view = new Zend_View;
        setcookie($key,$value/*, 0, "/", $view->serverUrl()*/); // unknown error occured on advanced parameters
    }

    public static function nonce(){
        return md5(uniqid(mt_rand(),true));
    }
    
    public static function getPastTense($verb){
        $translate = Zend_Registry::get('Zend_Translate');
        if ($verb == 'create' || $verb == 'update') {
            return $verb . 'd';
        } else if ($verb == 'tag'){
            return $translate->translate('is tagged in');
        } else {
            return $verb . 'ed';
        }
    }
    
    public static function getArticle($noun){
        if (preg_match('/^(a|e|i|o|u)\w+/i', $noun)) {
            return 'an';
        } else {
            return 'a';
        }
    }
    
    
    public static function getSession($session_namespace = 'Default'){
        return new Zend_Session_Namespace($session_namespace);
    }
    
    
    public static function initTimezoneSession(){
      // for not commly changed data we store them in session...
      if (!isset(Common::getSession()->timezone)) {
        $timezoneService = new Service_Timezone();
        Common::getSession()->timezone = $timezoneService -> get();
      }
      if (!isset($_SESSION['timezone_json'])) {
        $js = array();
        foreach (Common::getSession()->timezone as $timezone) {
          if (!in_array($timezone['country_code'], $js)) {
            $js[] = $timezone['country_code'];
            $js[] = array($timezone['timezone_id'], $timezone['city'] . ' (' . $timezoneService -> formatOffset($timezone['offset']) . ')');
          } else {
            $key = array_search($timezone['country_code'], $js);
            $js[$key + 1][] = $timezone['timezone_id'];
            $js[$key + 1][] = $timezone['city'] . ' (' . $timezoneService -> formatOffset($timezone['offset']) . ')';
          }
        }
        $_SESSION['timezone_json'] = json_encode($js);
      }
    }
    public static function initTreeSession(){
      if (!isset($_SESSION['tree_json']) || $_SESSION['tree_json'] == '[]') {
        $categoryService = new Service_Tree();
        $category = $categoryService -> get(false);
  
        $used_cats = array();
        $js = array();
        foreach ($category as &$cat) {
          if (!in_array($cat['category_ids'], $used_cats)) {
            $js[] = $cat['category_ids'];
            $js[] = array($cat['id'], $cat['name']);
            $used_cats[] = $cat['category_ids'];
          } else {
            $key = array_search($cat['category_ids'], $js);
            $js[$key + 1][] = $cat['id'];
            $js[$key + 1][] = $cat['name'];
          }
        }
        $_SESSION['tree_json'] = json_encode($js);
      }
    }
    
}