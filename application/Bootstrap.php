<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Cloud
 * @subpackage examples
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @category   Zend
 * @package    Zend_Cloud
 * @subpackage examples
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoload(){
        Zend_Controller_Action_HelperBroker::addPrefix('Helper');

        Zend_Layout::startMvc(APPLICATION_PATH . '/layouts');
        Zend_Layout::getMvcInstance()->setLayout('1-col-left');

        $documentType = new Zend_View_Helper_Doctype();
        $documentType->doctype('XHTML5');


        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);

        //Zend_Loader::registerAutoload();
    }
    
    protected function _initConfig()
    {
        $config_ini = new Zend_Config($this->getOptions(), true);
        Zend_Registry::set('config_ini', $config_ini);
        
       /*
        $display_lang = Common::getSiteDisplayLang();
               $translate = new Zend_Translate('csv', APPLICATION_PATH . '/configs/lang/'.$display_lang.'.csv', $display_lang);
               $translate->setLocale($display_lang);
               Zend_Registry::set('Zend_Translate', $translate);
               
               Zend_Registry::set('config', new Zend_Config(require APPLICATION_PATH . '/config.php'));
               
               Zend_Registry::set('feed_input_prefix', Zend_Registry::get('config_ini')->html->feed_inputs->prefix);*/
       
    }
    
    protected function _initActionDelimter(){
        $frontController = Zend_Controller_Front::getInstance();
        $dispatcher = $frontController->getDispatcher();
        $dispatcher->setWordDelimiter(array('_'));
    }
    
    protected function _initTimezone(){
        date_default_timezone_set("Asia/Hong_Kong");
    }
    
	protected function _initResourceInjector()
    {
    }
}
