<?php

class Plugins_Session extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request){
      Common::initTimezoneSession();
      Common::initTreeSession();
      Common::getSession()->baseAbsUrl = $request->getScheme() . '://' .$request->getHttpHost().Zend_Controller_Front::getInstance()->getBaseUrl().'/';
      Common::getSession()->currentUrl = $request->getScheme() . '://' .$request->getHttpHost(). $this->_request->getRequestUri();
      Common::getSession()->currentUrlNoGet = Common::getSession()->baseAbsUrl . $request->getControllerName().'/'. $request->getActionName();
	}
}

