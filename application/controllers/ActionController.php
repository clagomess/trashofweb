<?php
class ActionController extends Zend_Controller_Action {
    public function init(){
        $this->view->pathPUBLIC = Zend_Registry::get('application')->sys->url;
        
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
		
		General::setLanguage();
    }
    
    public function indexAction(){
	    echo '<pre>';
		print_r($_SESSION);
    }

    public function cadastrarAction(){
        self::requireIsPost();
        
        $param = array();
        $param['nome'] = trim($this->getRequest()->getParam('twunome'));
        $param['sobrenome'] = trim($this->getRequest()->getParam('twusobrenome')); 
        $param['email'] = strtolower(trim($this->getRequest()->getParam('twuemail'))); 
        $param['senha'] = $this->getRequest()->getParam('twusenha'); 
        $param['senha_r'] = $this->getRequest()->getParam('twusenha_r'); 
        $param['aceite_termo'] = $this->getRequest()->getParam('twuaceite_termo');
        
        if(
            !$param['nome'] &&
            !$param['email'] &&
            !$param['senha'] &&
            !$param['aceite_termo']
        ){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Favor, preencher os campos!")
            ));
            die();
        }
        
        $usuario = new UsuarioValida();
        
        $usuario->nome($param);
        $usuario->sobrenome($param);
        $param = $usuario->setNome($param);
        $usuario->email($param);
        $usuario->senha($param);
        $usuario->senha_r($param);
        $usuario->termo($param);
        
        $param['code'] = strtoupper(substr(base64_encode(uniqid('')),-9,6));
        
        unset($param['aceite_termo']);
        unset($param['senha_r']);
        
        $_SESSION['twc'] = $param;
        
        $mailcontent = $this->view->partial('helpers/mail_codecadastro.phtml',$param);
        $mailcontent = $this->view->partial('helpers/mail_layout.phtml', array(
            'title' => _("Trash Of WEB - E-mail de verificação"),
            'content' => $mailcontent
        ));
        
        General::mail(array(
            "email" => $param['email'],
            "title" => _("Trash Of WEB - E-mail de verificação"),
            "content" => $mailcontent
        ));
        
        echo json_encode(array(
            'status' => 1,
            'msg' => _("Foi enviado um e-mail para você com um código de validação.")
        ));
    }
    
    public function confirmaremailAction(){
        self::requireIsPost();
        
        $code = trim($this->getRequest()->getParam('twucode'));
        if(!$_SESSION['twc']){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Perdemos seu cadastro, volte e tente novamente.")
            ));
            die();
        }
        
        if(strlen($code) != 6 || strtoupper($code) != $_SESSION['twc']['code']){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("O código não condiz com o que foi enviado por e-mail.")
            ));
        }else{
            $locale = new Zend_Locale();
            
            Model_Usuario::getInstance()->cadastrar(array(
                'locale' => $locale->toString(),
                'nu_usuario' => uniqid(""),
                'no_usuario' => $_SESSION['twc']['nome'],
                'no_email' => $_SESSION['twc']['email'],
                'tx_senha' => $_SESSION['twc']['senha']
            ));
            
            $mailcontent = $this->view->partial('helpers/mail_novocadastro.phtml',$param);
            $mailcontent = $this->view->partial('helpers/mail_layout.phtml', array(
                'title' => _("Trash Of WEB - Seja bem vindo!"),
                'content' => $mailcontent
            ));
            
            General::mail(array(
                "email" => $_SESSION['twc']['email'],
                "title" => _("Trash Of WEB - Seja bem vindo!"),
                "content" => $mailcontent
            ));
            
            General::login(array(
                'no_email' => $_SESSION['twc']['email'],
                'tx_senha' => $_SESSION['twc']['senha']
            ));
            
            unset($_SESSION['twc']);
            
            echo json_encode(array(
                'status' => 1,
                'msg' => NULL
            ));
        }
    }
    
    public function postAction(){
        self::requireIsPost();
        General::permissaoAuth(false);
        
        $post = General::post($this->getRequest()->getParam('tx_publicar'));
        
        if($post){
            Model_Post::getInstance()->inserir(array(
                'co_usuario' => $_SESSION['tw_auth']['co_usuario'],
                'nu_post' => uniqid(""),
                'tx_post' => $post
            ));
            
            echo json_encode(array(
                'status' => 1,
                'msg' => 'success'
            ));
        }
    }
    
    public function comentariofieldAction(){
        General::permissaoAuth(false);
        echo $this->view->partial('helpers/box_publicar.phtml',array(
            'id' => 'boxpcom',
            'nu_post' => $this->getRequest()->getParam('nu_post')
        ));
    }
    
    public function comentarioAction(){
        self::requireIsPost();
        General::permissaoAuth(false);
        
        $post = General::post($this->getRequest()->getParam('tx_publicar'));
        
        if($post){
            Model_Comentario::getInstance()->inserir(array(
                'co_usuario' => $_SESSION['tw_auth']['co_usuario'],
                'nu_comentario' => uniqid(""),
                'tx_comentario' => $post,
                'nu_post' => $this->getRequest()->getParam('nu_post')
            ));
            
            echo json_encode(array(
                'status' => 1,
                'msg' => 'success'
            ));
        }
    }
    
    public function getpostAction(){
        self::requireIsPost();
        General::permissaoAuth(false);
            
        $param = array();
        $param['tipo'] = $this->getRequest()->getParam('tipo');
        $param['uptime'] = $this->getRequest()->getParam('uptime');
        $param['nu_usuario'] = $this->getRequest()->getParam('nu_usuario');
        
        $param = array_filter($param);
        
        $arPost = Model_Post::getInstance()->get($param);
        
        if(count($arPost)){
            foreach($arPost as $item){
                echo General::compressHtml($this->view->partial('helpers/li_post.phtml',$item));
            }
        }
    }
    
    public function getcomentarioAction(){
        self::requireIsPost();
        General::permissaoAuth(false);
        
        $arComentario = current(Model_Comentario::getInstance()->get(array(
            'nu_post' => $this->getRequest()->getParam('nu_post'),
            'limit' => $this->getRequest()->getParam('limit')
        )));
        
        if(count($arComentario)){
            foreach($arComentario as $item){
                $item['size'] = 32;
                echo General::compressHtml($this->view->partial('helpers/li_post.phtml',$item));
            }
        }
    }
    
    public function atualizarfotoAction(){
        General::permissaoAuth(false);
        $nu_imagem = uniqid();
        
        if($_FILES['Filedata']){
            $tmpPath = APPLICATION_PATH . "/../data/tmp/";
            
            $finfo = pathinfo($_FILES['Filedata']['name']);
            $name = $nu_imagem . '.' . $finfo['extension'];
            
            if(!in_array(strtolower($finfo['extension']),array('jpg','png','jpeg'))){
                echo json_encode(array(
                    'status' => 0,
                    'msg' => _("Extensão não permitida!")
                ));
                die();
            }
            
            @move_uploaded_file($_FILES['Filedata']['tmp_name'], $tmpPath . $name);
            
            try{
                copy($tmpPath . $name, $tmpPath . "128_" . $name);
                $canvas = canvas::Instance($tmpPath . "128_" . $name);
                $canvas->redimensiona(128,128,'crop');
                $canvas->grava($tmpPath . "128_" . $name, 90);
                
                copy($tmpPath . $name, $tmpPath . "70_" . $name);
                $canvas = canvas::Instance();
                $canvas->carrega($tmpPath . "70_" . $name);
                $canvas->redimensiona(70,70,'crop');
                $canvas->grava($tmpPath . "70_" . $name, 90);
                
                copy($tmpPath . $name, $tmpPath . "50_" . $name);
                $canvas = canvas::Instance();
                $canvas->carrega($tmpPath . "50_" . $name);
                $canvas->redimensiona(50,50,'crop');
                $canvas->grava($tmpPath . "50_" . $name, 90);
                
                copy($tmpPath . $name, $tmpPath . "32_" . $name);
                $canvas = canvas::Instance();
                $canvas->carrega($tmpPath . "32_" . $name);
                $canvas->redimensiona(32,32,'crop');
                $canvas->grava($tmpPath . "32_" . $name, 90);
                
                @unlink($tmpPath . $name);
            }
            catch(Exception $e){
                echo json_encode(array(
                    'status' => 0,
                    'msg' => 'Error!'
                ));
                
                @unlink($tmpPath . "32_" . $name);
                @unlink($tmpPath . "50_" . $name);
                @unlink($tmpPath . "70_" . $name);
                @unlink($tmpPath . "128_" . $name);
            }
            
            try{
                Model_Usuario::getInstance()->cadastrarFoto(array('nu_imagem' => $nu_imagem));
                
                $s3 = new S3(
                    Zend_Registry::get('application')->sys->aws_accesskey,
                    Zend_Registry::get('application')->sys->aws_secretkey
                );
                
                $bucket = Zend_Registry::get('application')->sys->aws_s3_bucket;
                
                $s3->putObjectFile($tmpPath . "32_" . $name, $bucket , "32_" . $name, S3::ACL_PUBLIC_READ);
                $s3->putObjectFile($tmpPath . "50_" . $name, $bucket , "50_" . $name, S3::ACL_PUBLIC_READ);
                $s3->putObjectFile($tmpPath . "70_" . $name, $bucket , "70_" . $name, S3::ACL_PUBLIC_READ);
                $s3->putObjectFile($tmpPath . "128_" . $name, $bucket , "128_" . $name, S3::ACL_PUBLIC_READ);
                
                @unlink($tmpPath . "32_" . $name);
                @unlink($tmpPath . "50_" . $name);
                @unlink($tmpPath . "70_" . $name);
                @unlink($tmpPath . "128_" . $name);
            }
            catch(Exception $e){
                echo json_encode(array(
                    'status' => 0,
                    'msg' => _("Erro ao processar as imagens")
                ));
                
                @unlink($tmpPath . "32_" . $name);
                @unlink($tmpPath . "50_" . $name);
                @unlink($tmpPath . "70_" . $name);
                @unlink($tmpPath . "128_" . $name);
            }
            
            $_SESSION['tw_auth']['nu_imagem'] = $nu_imagem;
            
            $url_imagem = General::urlImgUser(array(
                'nu_imagem' => $nu_imagem,
                'size' => 50
            ));
            
            echo json_encode(array(
                'status' => 1,
                'msg' => 'success',
                'nu_imagem' => $nu_imagem,
                'url_imagem' => $url_imagem
            ));
        }
    }
    
    public function atualizarcadastroAction(){
        self::requireIsPost();
        General::permissaoAuth(false);
        
        $param = array();
        $param['nome'] = trim($this->getRequest()->getParam('twu_nome'));
        $param['sobrenome'] = trim($this->getRequest()->getParam('twu_sobrenome')); 
        $param['senha_n'] = $this->getRequest()->getParam('twu_senha_n'); 
        $param['senha'] = $this->getRequest()->getParam('twu_senha'); 
        $param['senha_r'] = $this->getRequest()->getParam('twu_senha_r'); 
        $param['pais'] = $this->getRequest()->getParam('twu_pais');
        $param['st_email'] = $this->getRequest()->getParam('twu_st_email');
        $param['idioma'] = $this->getRequest()->getParam('twu_idioma');
        $param['fuso'] = $this->getRequest()->getParam('twu_fuso');
        
        $usuario = new UsuarioValida();
        $usuario->nome($param);
        $usuario->sobrenome($param);
        $param = $usuario->setNome($param);
        if($param['senha']){
            $usuario->senha_n($param);
            $usuario->senha($param);
            $usuario->senha_r($param);
        }
        
        $param = array_filter($param);
        
        Model_Usuario::getInstance()->alterar(array(
            "co_pais" => $param['pais'],
            "tx_senha" => $param['senha'],
            "no_usuario" => $param['nome'],
            "co_pais" => $param['pais'],
            "co_fuso" => $param['fuso'],
            "co_idioma" => $param['idioma']
        ));
        
        $_SESSION['tw_auth'] = Model_Usuario::getInstance()->get(array(
            'co_usuario' => $_SESSION['tw_auth']['co_usuario'],
            'fields' => array(
                'co_usuario', 'co_pais', 'no_usuario',
                'no_email', 'st_plus', 'st_email',
                'sg_idioma', 'dt_fuso', 'nu_imagem',
                'nu_usuario', 'co_idioma'
            )
        ));
        
        echo json_encode(array(
            'status' => 1,
            'msg' => _("Alterado com sucesso!")
        ));
    }
    
    public function setseguidorAction(){
        self::requireIsPost();
        General::permissaoAuth(false);
        
        $param = array();
        $param['nu_usuario'] = $this->getRequest()->getParam('nu_usuario');
        $param['modo'] = $this->getRequest()->getParam('modo');
        
        Model_Usuario::getInstance()->setSeguidor($param);
        
        echo json_encode(array(
            'status' => 1,
            'msg' => "success",
            'html' => $this->view->partial('helpers/bt_seguir.phtml',array(
                'modo' => ($param['modo'] == 'seguir' ? '' : 'seguir'),
                'nu_usuario' => $param['nu_usuario']
            ))
        ));
    }
    
    /**
     * Não permite GET
     */
    private function requireIsPost(){
        if(!$this->getRequest()->isPost()){
            echo json_encode(array(
                'status' => 0,
                'msg' => 'Error!'
            ));
            die();
        }
    }
}
?>