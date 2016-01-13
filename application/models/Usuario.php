<?php
class Model_Usuario extends Zend_Db_Table {
	private static $_instance = null;
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new Model_Usuario();
		}
		return self::$_instance;
	}
    
    public function emailExiste($email){
        $sql = "SELECT no_email FROM tb_usuario WHERE no_email = :email";
        $ar = $this->getAdapter()->fetchRow($sql,array('email' => $email));
        if($ar){
            return true;
        }else{
            return false;
        }
    }
    
    public function cadastrar($param){
        $sql = "INSERT INTO tb_usuario (
	                co_pais,co_fuso,co_idioma,
	                nu_usuario,no_usuario,no_email,
	                tx_senha,dt_cadastro,dt_ultimo_acesso
                )
                SELECT
	                p.co_pais, p.co_fuso, p.co_idioma,
	                :nu_usuario,:no_usuario,:no_email,
	                MD5(:tx_senha), UTC_TIMESTAMP(),UTC_TIMESTAMP()
                FROM tb_pais p
                JOIN tb_idioma i ON i.co_idioma = p.co_idioma
                WHERE i.sg_idioma = IFNULL((
	                SELECT sg_idioma
	                FROM tb_idioma 
	                WHERE sg_idioma = :locale
                ),'en-US')";
        
        $this->getAdapter()->prepare($sql)->execute($param);
        General::cache()->clean(Zend_Cache::CLEANING_MODE_ALL);
    }
    
    public function alterar($param){
        $bind = array();
        $sql = "UPDATE tb_usuario SET ";
        if($param['co_pais']){
            $sql .= "co_pais = :co_pais, ";
            $bind['co_pais'] = $param['co_pais'];
        }
        
        if($param['co_fuso']){
            $sql .= "co_fuso = :co_fuso, ";
            $bind['co_fuso'] = $param['co_fuso'];
        }
        
        if($param['co_idioma']){
            $sql .= "co_idioma = :co_idioma, ";
            $bind['co_idioma'] = $param['co_idioma'];
        }
        
        if($param['co_imagem']){
            $sql .= "co_imagem = :co_imagem, ";
            $bind['co_imagem'] = $param['co_imagem'];
        }
        
        if($param['no_usuario']){
            $sql .= "no_usuario = :no_usuario, ";
            $bind['no_usuario'] = $param['no_usuario'];
        }
        
        if($param['tx_senha']){
            $sql .= "tx_senha = md5(:tx_senha), ";
            $bind['tx_senha'] = $param['tx_senha'];
        }
        
        if($param['st_ativo']){
            $sql .= "st_ativo = :st_ativo, ";
            $bind['st_ativo'] = $param['st_ativo'];
        }
        
        if($param['st_email']){
            $sql .= "st_email = :st_email,";
            $bind['st_email'] = $param['st_email'];
        }
        
        $sql .= "dt_ultimo_acesso = UTC_TIMESTAMP() ";
        $sql .= "WHERE co_usuario = :co_usuario";
        $bind['co_usuario'] = $_SESSION['tw_auth']['co_usuario'];
        
        General::clearCache('usr');
        $this->getAdapter()->prepare($sql)->execute($bind);
    }
    
    public function get($param){
        $bind = array();
        
        if(!$param['fields']){
            $param['fields'] = array(
                'co_usuario', 'nu_usuario', 'co_pais',
                'co_imagem', 'no_usuario', 'no_email',
                'dt_cadastro', 'dt_ultimo_acesso', 'st_plus',
                'st_email', 'co_fuso', 'co_idioma'
            );
        }
        
        $sql  = "SELECT ";
        $sql .= (in_array('co_usuario',$param['fields']) ? 'u.co_usuario, ' : '');
        $sql .= (in_array('nu_usuario',$param['fields']) ? 'u.nu_usuario, ' : '');
        $sql .= (in_array('co_pais',$param['fields']) ? 'u.co_pais, ' : '');
        $sql .= (in_array('co_idioma',$param['fields']) ? 'u.co_idioma, ' : '');
        $sql .= (in_array('co_fuso',$param['fields']) ? 'u.co_fuso, ' : '');
        $sql .= (in_array('co_imagem',$param['fields']) ? 'u.co_imagem, ' : '');
        $sql .= (in_array('no_usuario',$param['fields']) ? 'u.no_usuario, ' : '');
        $sql .= (in_array('no_email',$param['fields']) ? 'u.no_email, ' : '');
        $sql .= (in_array('st_plus',$param['fields']) ? 'u.st_plus, ' : '');
        $sql .= (in_array('st_email',$param['fields']) ? 'u.st_email, ' : '');
        $sql .= (in_array('nu_imagem',$param['fields']) ? 'i.nu_imagem, ' : '');
        $sql .= (in_array('sg_idioma',$param['fields']) ? 'l.sg_idioma, ' : '');
        
        if(in_array('dt_cadastro',$param['fields'])){
            $sql .= "date_format(u.dt_cadastro,'%d/%m/%Y %H:%i') dt_cadastro, ";
        }
        
        if(in_array('dt_ultimo_acesso',$param['fields'])){
            $sql .= "date_format(u.dt_ultimo_acesso,'%d/%m/%Y %H:%i') dt_ultimo_acesso, ";
        }
        
        if(in_array('dt_fuso',$param['fields'])){
            $sql .= "date_format(f.dt_fuso,'%H:%i') dt_fuso, ";
        }
        
        if(in_array('seg_count',$param['fields'])){
            $sql .= "(
                SELECT count(*) cnt
                FROM tb_seguidor s
                JOIN tb_usuario u ON u.co_usuario = s.co_usuario_seguidor
                WHERE co_usuario_seguido = :co_usuario
                AND u.st_ativo = 1
            ) seguidor, ";
            $sql .= "(
                SELECT count(*) cnt
                FROM tb_seguidor s
                JOIN tb_usuario u ON u.co_usuario = s.co_usuario_seguido
                WHERE co_usuario_seguidor = :co_usuario
                AND u.st_ativo = 1
            ) seguido, ";
        }
        
        if(in_array('seguidor',$param['fields'])){
            $sql .= "(SELECT 1
                FROM tb_seguidor
                WHERE co_usuario_seguido = :co_usuario 
                AND co_usuario_seguidor = :co_usuario_auth) seg_vinculo, ";
            $bind['co_usuario_auth'] = $_SESSION['tw_auth']['co_usuario'];
        }
        
        $sql .= " 1 as miau FROM tb_usuario u ";
        
        if(in_array('nu_imagem',$param['fields'])){
            $sql .= " LEFT JOIN tb_imagem i ON i.co_imagem = u.co_imagem ";
        }
        
        if(in_array('sg_idioma',$param['fields'])){
            $sql .= " JOIN tb_idioma l ON l.co_idioma = u.co_idioma ";
        }
        
        if(in_array('dt_fuso',$param['fields'])){
            $sql .= " JOIN tb_fuso f ON f.co_fuso = u.co_fuso ";
        }
        
        $sql .= " WHERE 1=1 ";
        
        if($param['co_usuario']){
            $sql .= " AND co_usuario = :co_usuario ";
            $bind['co_usuario'] = $param['co_usuario'];
        }
        
        if($param['nu_usuario']){
            $sql .= " AND nu_usuario = :nu_usuario";
            $bind['nu_usuario'] = $param['nu_usuario'];
            if(in_array('seg_count',$param['fields'])){
                $ssql = "SELECT co_usuario 
                        FROM tb_usuario 
                        WHERE nu_usuario = :nu_usuario";
                
                $rrs = $this->getAdapter()->fetchRow($ssql,array(
                    'nu_usuario' => $param['nu_usuario']
                ));
                
                $bind['co_usuario'] = $rrs['co_usuario'];
            }
        }
        
        if(isset($param['no_email'])){
            $sql .= " AND no_email = :no_email ";
            $bind['no_email'] = $param['no_email'];
        }
        
        if(isset($param['tx_senha'])){
            $sql .= " AND tx_senha = MD5(:tx_senha) ";
            $bind['tx_senha'] = $param['tx_senha'];
        }
        
        $cache = General::cache(60 * 10);
        $cache_id = "usr_" . md5(serialize($param) . serialize($bind));
        General::setCacheID('usr',$cache_id);
        
        $rs = $cache->load($cache_id);
        
        if(!$rs){
            $rs = $this->getAdapter()->fetchRow($sql,$bind);
            $cache->save($rs, $cache_id);
        }
        
        return $rs;
    }
    
    public function login($param){
        General::clearCache('usr');
        
        $ar = self::get(array(
            'tx_senha' => $param['tx_senha'],
            'no_email' => $param['no_email'],
            'fields' => array(
                'co_usuario', 'co_pais', 'no_usuario',
                'no_email', 'st_plus', 'st_email',
                'sg_idioma', 'dt_fuso', 'nu_imagem',
                'nu_usuario'
            )
        ));
        
        if($ar){
            $sql = "UPDATE tb_usuario SET dt_ultimo_acesso = UTC_TIMESTAMP() 
                    WHERE co_usuario = {$ar['co_usuario']}";
            $this->getAdapter()->prepare($sql)->execute();
            return $ar;
        }else{
            return false;
        }
    }
    
    public function seguidor($param = array()){
        $sql = "SELECT 
	                u.nu_usuario,
	                u.no_email,
	                u.no_usuario,
                    i.nu_imagem
                FROM tb_seguidor s
                JOIN tb_usuario u ";
        
        if($param['tipo'] == "seguidor"){
            $sql .= "ON u.co_usuario = s.co_usuario_seguidor";
        }else{
            $sql .= "ON u.co_usuario = s.co_usuario_seguido";
        }
        
        $sql .= " LEFT JOIN tb_imagem i ON
                    i.co_imagem = u.co_imagem
                WHERE ";
        
        if($param['tipo'] == "seguidor"){
            $sql .= "s.co_usuario_seguido = :co_usuario";
        }else{
            $sql .= "s.co_usuario_seguidor = :co_usuario";
        }
                
        $sql .= " AND u.st_ativo = 1
                ORDER BY u.dt_ultimo_acesso desc, i.nu_imagem DESC
                LIMIT 12";
        
        $bind = array();
        $bind['co_usuario'] = $param['co_usuario'];
        
        $cache = General::cache(60 * 10);
        $cache_id = "seg_" . md5(serialize($param) . serialize($bind));
        General::setCacheID('seg',$cache_id);
        
        $rs = $cache->load($cache_id);
        
        if(!$rs){
            $rs = $this->getAdapter()->fetchAll($sql,$bind);
            $cache->save($rs, $cache_id);
        }
        
        return $rs;
    }
    
    public function cadastrarFoto($param){
        $this->getAdapter()->beginTransaction();
        $sql = "INSERT INTO tb_imagem (nu_imagem) VALUES (:nu_imagem)";
        $this->getAdapter()->prepare($sql)->execute($param);
        self::alterar(array(
            'co_imagem' => $this->getAdapter()->lastInsertId('co_imagem')
        ));
        $this->getAdapter()->commit();
    }
    
    public function getPais(){
        $sql = "SELECT co_pais, CONCAT(sg_pais,' - ',no_pais) no_pais 
                FROM tb_pais ORDER BY no_pais";
        return $this->getAdapter()->fetchAll($sql);
    }
    
    public function getFuso(){
        $sql = "SELECT
                co_fuso,
                CONCAT(
	                DATE_FORMAT(dt_fuso,'%H:%i'),
	                ' - ',
	                no_fuso
                ) no_fuso
                FROM tb_fuso
                ORDER BY dt_fuso";
        return $this->getAdapter()->fetchAll($sql);
    }
    
    public function getIdioma(){
        $sql = "SELECT co_idioma, no_idioma 
                FROM tb_idioma ORDER BY no_idioma";
        return $this->getAdapter()->fetchAll($sql);
    }
    
    public function setSeguidor(&$param){
        if($param['modo'] == "seguir"){
            $sql = "INSERT INTO tb_seguidor 
                    SELECT :co_usuario_seguidor, co_usuario 
                    FROM tb_usuario 
                    WHERE nu_usuario = :nu_usuario_seguido";
        }else{
            $sql = "DELETE FROM tb_seguidor 
                    WHERE co_usuario_seguidor = :co_usuario_seguidor
                    AND co_usuario_seguido = (
	                    SELECT co_usuario 
	                    FROM tb_usuario 
	                    WHERE nu_usuario = :nu_usuario_seguido
                    )";
        }
        
        $this->getAdapter()->prepare($sql)->execute(array(
            'co_usuario_seguidor' => $_SESSION['tw_auth']['co_usuario'],
            'nu_usuario_seguido' => $param['nu_usuario']
        ));
        
        General::clearCache('seg');
        General::clearCache('usr');
        General::clearCache('pst');
    }
}
?>