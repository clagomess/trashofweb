<?php
class IndexController extends Zend_Controller_Action {
    public function init(){
        $this->view->pathPUBLIC = Zend_Registry::get('application')->sys->url;
        $this->view->canonical = $this->view->url();
        
        General::setLanguage();
    }
    
    public function indexAction(){
	    $this->view->indexpage = true;
        
        if($this->getRequest()->isPost()){
            $auth = General::login(array(
                'no_email' => $this->getRequest()->getParam('twa_email'),
                'tx_senha' => $this->getRequest()->getParam('twa_senha')
            ));
            if($auth){
                header("Location: " . $this->view->url(General::mUrl("home")));
            }else{
                $this->view->login_error = _("Login ou senha incorretos!");
            }
        }
        
        if(General::isAuth()){
            $goto = $this->getRequest()->getParam('goto');
            $goto = urldecode($goto);
            
            if($goto){
                header("Location: {$goto}");
            }else{
                header("Location: " . $this->view->url(General::mUrl("home")));
            }
        }
    }
}
?>