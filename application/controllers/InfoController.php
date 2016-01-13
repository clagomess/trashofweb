<?php
class InfoController extends Zend_Controller_Action {
    public function init(){
        $this->view->canonical = $this->view->url();
        General::setLanguage();
    }
    
    public function indexAction(){
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        header("Location: " . $this->view->url(General::mUrl("home")));
    }
    
    public function termAction(){
        $this->view->content = 800;
        //tem view
    }
}
?>