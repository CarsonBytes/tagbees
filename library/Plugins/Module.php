<?php

class Plugins_Module extends Zend_Controller_Plugin_Abstract
{
	
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		$module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
	    $layout = Zend_Layout::getMvcInstance();
		$viewRenderer = Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer');
        
        Zend_Registry::set('request', $request);
		
		//echo '<pre>';var_dump($module);echo '</pre>';die();
	    
		if ($module == 'admin'){
	    	if (!Zend_Auth::getInstance()->hasIdentity()){
            	$redirect = new Zend_Controller_Action_Helper_Redirector();
	            $redirect->gotoUrlAndExit('auth/login?redirect='.urlencode($request->REQUEST_URI));
	    	}else{
	    		$identity = Zend_Auth::getInstance()->getIdentity();
	    		if ($identity->is_admin!=1) throw new Exception('not an admin');
				//$layout->disableLayout();
	    		$layout->setLayout('admin/1-col-left');
	    	}
		}else if ($module == 'ajax'){
			if (!$request->isXmlHttpRequest()){
				exit(0);
			}else{
				$layout->disableLayout();
	            //if ($controller != 'dialog') $viewRenderer->setNeverRender(true);
			}
		}else{
            if (!$request->isXmlHttpRequest()){
                $layout->setLayout('1-col-left');
            }else{
                $layout->disableLayout();
            }
        }
	}
}

