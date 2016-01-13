<?php
/**
 * Classe com funções importantes para todo sistema
 */
class General extends Zend_Controller_Action {    
    /**
     * Transforma uma string em array para $this->view->url
     * @param $string
     * @return array
     */
    public function mUrl($string){
        $ar = explode('/',$string);
        $arParam['controller'] = $ar[0];
        $arParam['action'] = $ar[1];
        $i = 2;
        while(isset($ar[$i])){
            $arParam[$ar[$i]] = $ar[$i + 1];    
            $i += 2;
        }
        return $arParam;
    }
    
    /**
     * Define uma pagina autenticada
     * @param $redirect é um redirecionamento
     */
    public function permissaoAuth($redirect = true){
        if(!self::isAuth()){
            if($redirect){
                $url  = $this->view->url(General::mUrl('index'),null,true);
                $url .= "?goto=" . urlencode($_SERVER['REQUEST_URI']);
                
                header("Location: " . $url);
            }else{
                echo json_encode(array(
                    'status' => 0,
                    'msg' => _("Permissão negada.")
                ));
            }
            
            die();
        }
    }
    
    /**
     * Está autenticado
     * @return bool
     */
    public function isAuth(){
        return is_array($_SESSION['tw_auth']);
    }
    
    public function login($param){
        $arUser = Model_Usuario::getInstance()->login($param);
        if($arUser){
            $_SESSION['tw_auth'] = $arUser;
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Define uma url de imagem para perfil do usuário
     * @param (array|object)(no_email,fb_id,size)
     * @return
     */
    public function urlImgUser($param){
        $url = "";
        
        if(is_object($param)){
            $no_email = $param->no_email;
            $nu_imagem = $param->nu_imagem;
            $size = $param->size;
        }else{
            $no_email = $param['no_email'];
            $nu_imagem = $param['nu_imagem'];
            $size = $param['size'];
        }
        
        if($nu_imagem){
            $url = "http://";
            $url .= Zend_Registry::get('application')->sys->aws_s3_bucket . "/";
            $url .= ($size?$size:50) . "_" . $nu_imagem . ".jpg";
            return $url;
        }else{
            $url = Zend_Registry::get('application')->sys->url;
            $url .= "img/thumb_".($size?$size:50).".jpg";
        }
        
        return $url;
    }
    
    /**
     * encapsulamento de envio de e-mail
     * @param (content,email,title)
     * @return bool
     */
    public function mail($param){
        try{
            $mail = new Zend_Mail("UTF-8");
            $mail->setBodyHtml($param['content']);
            $mail->addTo($param['email']);
            $mail->setSubject($param['title']);
            $mail->send();
            
            return true;
        }
        catch(Exception $e){
            return false;
        }
    }
    
    /**
     * Valida post/comentário enviado por usuário
     * @param $text
     * @return (string|bool)
     */
    public function post($text){
        $text = urldecode($text);
        $text = trim($text);
        $text = substr($text,0,200);
        
        if(strlen($text) > 0){
            return $text;
        }else{
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Conteúdo inválido.")
            ));
            return false;
        }
    }
    
    /**
     * Faz um tratamento antes de printar um texto para o usuário
     * @param $text
     * @return (string)
     */
    public function getPost($text){
        return nl2br(htmlspecialchars($text));
    }
    
    /**
     * Minifica uma data timestamp
     * @param $strData
     */
    public function dataMin($strData, $strDataUTC){        
        $dt_C = strtotime($strData);
        $dt_D = time() - strtotime($strDataUTC);
        
        if($dt_D <= 5){
            $return = "Agora"; 
        }else if($dt_D < 60){
            $return = $dt_D . "s"; 
        }else if($dt_D == 60){
            $return = " 1m";
        }else if($dt_D < (60 * 60)){ 
            $return = floor($dt_D / 60) . "m";
        }else if($dt_D == (60 * 60)){ 
            $return = "1h";
        }else if($dt_D < (60 * 60 * 24)){ 
            $return = floor($dt_D / (60 * 24)) . "h";   
        }else{
            $return = date('d/m/y H:i',$dt_C); 
        }

        return $return;
    }
    
    /**
     * Zend_Cache
     * Default: 60s
     * @return
     */
    public function cache($lifetime = 60){
        return Zend_Cache::factory(
		    'Core',
            'File',
            array(
                'lifetime' => $lifetime,
                'automatic_serialization' => true
            ),
            array(
	   	        'cache_dir'  => APPLICATION_PATH . '/../data/cache/'
            )
        );
    }
    
    /**
     * Salva os IDs de cache na sessão
     * @param $domain origem
     * @param $id do cache
     */
    public function setCacheID($domain,$id){
        $_SESSION['tw_cache'][$domain][] = $id;
        $_SESSION['tw_cache'][$domain] = array_unique($_SESSION['tw_cache'][$domain]);
    }
    
    public function clearCache($domain){
        if(count($_SESSION['tw_cache'][$domain])){
            foreach($_SESSION['tw_cache'][$domain] as $item){
                self::cache()->remove($item);
            }
            unset($_SESSION['tw_cache'][$domain]);
        }
    }
    
    public function getUserID(){
        return $_SESSION['tw_auth']['co_usuario'];
    }
    
    public function url_profile($param){
        if(is_object($param)){
            $nu_usuario = $param->nu_usuario;
        }else{
            $nu_usuario = $param['nu_usuario'];
        }
        
        return $this->url(General::mUrl("home/profile/id/{$nu_usuario}"),null,true);
    }
    
    public function url_post($param){
        if(is_object($param)){
            $nu_post = $param->nu_post;
        }else{
            $nu_post = $param['nu_post'];
        }
        
        return $this->url(General::mUrl("home/post/id/{$nu_post}"),null,true);
    }
    
    /**
     * Muda o contexto de linguagem
     * @param $param
     */
    public function setLanguage(){
		$locale = $_SESSION['tw_auth']['sg_idioma'];
	
        if(!$locale){
            $locale = new Zend_Locale();
            $locale = $locale->toString();
        }
        
        $locale  = str_replace('-','_',$locale);
		$locale .= ".utf-8";
        
        $localeDIR = APPLICATION_PATH . "/../public/locale/";
        
        putenv("LC_ALL=" . $locale);
        setlocale(LC_ALL, $locale);
        
		bindtextdomain('messages', $localeDIR);
		textdomain('messages');
    }
    
    public function compressHtml($content){
        while(
            preg_match("/  /", $content) || 
            preg_match("/\t\t/", $content) ||
            preg_match("/\r\n\r\n/", $content)
        ){
            $content = str_replace("  ",' ',$content);
            $content = str_replace("\t\t",' ',$content);
            $content = str_replace("\r\n\r\n",' ',$content);
        }
        
        $content = str_replace("\r\n",' ',$content);
        $content = str_replace("  ",' ',$content);
        
        return $content;
    }
}
?>