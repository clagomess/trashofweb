<?php
/**
 * Classe com validações de campos usuário
 */
class UsuarioValida {
    public function setNome(&$param){
        $param['nome'] = current(explode(' ',$param['nome']));
        $param['sobrenome'] = current(explode(' ',$param['sobrenome']));
        $param['nome'] .= ' ' . $param['sobrenome'];
        unset($param['sobrenome']);
        
        return $param;
    }
    
    public function nome(&$param){
        if(strlen($param['nome']) < 3){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Nome inválido!")
            ));
            die();
        }
    }
    
    public function sobrenome(&$param){
        if(strlen($param['sobrenome']) < 3){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Sobrenome inválido!")
            ));
            die();
        }
    }
    
    public function email(&$param){
        if(!filter_var($param['email'], FILTER_VALIDATE_EMAIL)){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Seu e-mail parece ser inválido.")
            ));
            die();
        }
        
        if(Model_Usuario::getInstance()->emailExiste($param['email'])){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Esse e-mail já existe.")
            ));
            die();
        }
    }
    
    public function senha(&$param){
        if(!(strlen($param['senha']) >= 6 && strlen($param['senha']) <= 20)){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("A senha deve ter de 6 a 20 caracteres!")
            ));
            die();
        }
    }
    
    public function senha_r(&$param){
        if($param['senha'] != $param['senha_r']){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("As senhas não são iguais!")
            ));
            die();
        }
    }
    
    public function senha_n(&$param){
        $rs = Model_Usuario::getInstance()->login(array(
            'tx_senha' => $param['senha_n'],
            'no_email' => $_SESSION['tw_auth']['no_email']
        ));
        
        if(!$rs){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Sua senha está incorreta!")
            ));
            die();
        }
    }
    
    public function termo(&$param){
        if($param['aceite_termo'] != 'S'){
            echo json_encode(array(
                'status' => 0,
                'msg' => _("Para se cadastrar, é necessário ler e concordar com os termos")
            ));
            die();
        }
    }
}
?>