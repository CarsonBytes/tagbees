<?php

class Plugins_PageLog extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request){
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $params=$request->getParams();
		/*page type was for feed page type, for now as it appears only on main page, this check is not needed
         * if (strpos($_SERVER['REQUEST_URI'], 'profile')!==false){
                    $page_type='profile';
                }else if (strpos($_SERVER['REQUEST_URI'], 'post')!==false){
                    $page_type='post';
                }else if (strpos($_SERVER['REQUEST_URI'], 'search')!==false){
                    $page_type='search';
                }else{
                    $page_type='';
                }
                Zend_Registry::set('page_type',$page);*/
                
        // to grap urls that are in default module, not in auth controller, and not an error url 
    	$controller2= Zend_Controller_Front::getInstance();
		if ($controller2->getDispatcher()->isDispatchable($request) 
			&& ( $module == 'default' || $module == NULL )
            && $controller != 'auth'
            && ( !isset($params['error_handler']))
		) {
		    
            Zend_Registry::set('page_type', $controller);
            
            // init 2 session variables: the current and last qualified url
            if (!isset(Common::getSession()->redirect)) Common::getSession()->redirect = '';
            if (!isset(Common::getSession()->last_visited_url)) Common::getSession()->last_visited_url = '';
            
            // tempurl is to save current qualified url temporarily to ensure current and last qualified url will not be same
            if (!isset($tempUrl)) {
	    	$tempUrl = '';
	    	$tempParams = array();
	    }
            if (Common::getSession()->last_visited_url != Common::getSession()->redirect) {
                $tempUrl = Common::getSession()->redirect;
                $tempParams = Common::getSession()->redirect_params;
            }
            
            // save current qualified url
        	Common::getSession()->redirect=$request->getRequestUri();
            Common::getSession()->redirect_params = $params;
            
            // to ensure there are no duplicated urls due to browser refresh 
            if ($tempUrl != Common::getSession()->redirect){
                Common::getSession()->last_visited_url = $tempUrl;
                Common::getSession()->last_visited_url_params = $tempParams;
            }
        }
        //echo '<pre>';var_dump(Common::getSession()->last_visited_url);echo '</pre>';
        //echo '<pre>';var_dump(Common::getSession()->last_visited_url_params);echo '</pre>';
        //echo '<pre>';var_dump(Common::getSession()->redirect_params);echo '</pre>';
        //echo '<pre>';var_dump(Common::getSession()->redirect);echo '</pre>';
		//$common=new Common();
		//$common->saveLog($module,$controller,$action,$params);
	}
}

