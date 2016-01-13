<?php
class HomeController extends Zend_Controller_Action {
    public function init(){
        $this->view->pathPUBLIC = Zend_Registry::get('application')->sys->url;
        $this->view->canonical = $this->view->url();
        $this->view->auth = $_SESSION['tw_auth'];
        
        General::cache()->clean(Zend_Cache::CLEANING_MODE_OLD);
		General::setLanguage();
    }
    
    /**
     * Pсgina de pouso rede
     */
    public function indexAction(){
        General::permissaoAuth();
        
        $this->view->usuario = Model_Usuario::getInstance()->get(array(
            'co_usuario' => $this->view->auth['co_usuario'],
            'fields' => array(
                'nu_usuario', 'nu_imagem', 'seg_count',
                'no_usuario', 'co_usuario'
            )
        ));
        
        $this->view->title = $this->view->usuario['no_usuario'];
        
        $this->view->arPostRede = Model_Post::getInstance()->get(array(
            'tipo' => 'rede'
        ));
        
        if($this->view->usuario['seguido'] > 0){
            $this->view->arPostAmigo = Model_Post::getInstance()->get(array(
                'tipo' => 'amigo'
            ));
            
            $this->view->arSeguindo = Model_Usuario::getInstance()->seguidor(array(
                'co_usuario' => $this->view->auth['co_usuario']
            ));
        }
        
        if($this->view->usuario['seguidor'] > 0){
            $this->view->arSeguidor = Model_Usuario::getInstance()->seguidor(array(
                'tipo' => 'seguidor',
                'co_usuario' => $this->view->auth['co_usuario']
            ));
        }
    }
    
    /**
     * Pсgina de configuraчуo de dados do usuсrio
     */
    public function configAction(){
        General::permissaoAuth();
        $this->view->content = 800;
        
        $this->view->usuario = Model_Usuario::getInstance()->get(array(
            'co_usuario' => $_SESSION['tw_auth']['co_usuario']
        ));
        
        $this->view->pais = Model_Usuario::getInstance()->getPais();
        $this->view->fuso = Model_Usuario::getInstance()->getFuso();
        $this->view->idioma = Model_Usuario::getInstance()->getIdioma();
    }
    
    /**
     * Visualizaчуo perfil individual, e nуo autenticado
     */
    public function profileAction(){
        $this->view->content = 800;
        $this->view->isProfile = true;
        
        $this->view->usuario = Model_Usuario::getInstance()->get(array(
            'nu_usuario' => $this->getRequest()->getParam('id'),
            'fields' => array(
                'nu_usuario', 'nu_imagem', 'seg_count',
                'no_usuario', 'co_usuario', 'seguidor'
            )
        ));
        
        if($this->view->usuario){
            $this->view->title = $this->view->usuario['no_usuario'];
            $this->view->usuario['size'] = 128;
            $this->view->favicon = General::urlImgUser($this->view->usuario);
            
            $this->view->post = Model_Post::getInstance()->get(array(
                'tipo' => 'usuario',
                'co_usuario' => $this->view->usuario['co_usuario']
            ));
            
            if($this->view->usuario['seguido'] > 0){            
                $this->view->arSeguindo = Model_Usuario::getInstance()->seguidor(array(
                    'co_usuario' => $this->view->usuario['co_usuario']
                ));
            }
            
            if($this->view->usuario['seguidor'] > 0){
                $this->view->arSeguidor = Model_Usuario::getInstance()->seguidor(array(
                    'tipo' => 'seguidor',
                    'co_usuario' => $this->view->usuario['co_usuario']
                ));
            }
        }else{
            $this->_helper->viewRenderer->setNoRender();
            echo $this->view->partial('helpers/404.phtml');
        }
    }
    
    /**
     * Visualizaчуo post individual, e nуo autenticado
     */
    public function postAction(){
        $this->view->content = 400;
        $this->view->nu_post = $this->getRequest()->getParam('id');
        $this->view->post = Model_Post::getInstance()->get(array(
            'tipo' => 'id',
            'nu_post' => $this->getRequest()->getParam('id')
        ));
        
        $this->view->title = "Post";
        $this->view->description = current($this->view->post);
        $this->view->description = $this->view->description['tx_post'];
        
        if(!$this->view->post){
            $this->_helper->viewRenderer->setNoRender();
            echo $this->view->partial('helpers/404.phtml');
        }
    }
    
    /**
     * Sair do sistema
     */
    public function logoutAction(){
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        // por preucaчуo
        unset($_SESSION['tw_auth']);
        
        session_destroy();
        
        header("Location: " . $this->view->url(General::mUrl('index')));
    }
}
?>