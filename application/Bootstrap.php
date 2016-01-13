<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap{
	protected function _initConfig() {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV, true);
        Zend_Registry::getInstance()->set('application', $config);

        if(!session_id()){
            session_start();
        }

        $ar = explode('/',str_replace('/index.php','',$_SERVER['SCRIPT_NAME']));
        $config->sys->url = implode('/',$ar) . '/public/';
    }
    
    protected function _initAutoLoad() {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath' => APPLICATION_PATH
        ));

        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);
        return $autoloader;
    }
}
?>
