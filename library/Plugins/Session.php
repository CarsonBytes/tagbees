<?php

class Plugins_Session extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request){
      Common::initTimezoneSession();
      Common::initTreeSession();
	}
}

