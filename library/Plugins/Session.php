<?php

class Plugins_Session extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request){
      Common::initTimezoneSession();
      Common::initTreeSession();
      Common::getSession()->baseUrl = $request->getScheme() . '://' .$request->getHttpHost().Zend_Controller_Front::getInstance()->getBaseUrl();
      Common::getSession()->currentUrl = $request->getScheme() . '://' .$request->getHttpHost(). $this->_request->getRequestUri();
	}
}

