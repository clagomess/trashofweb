<?php
class Model_Post extends Zend_Db_Table {
	private static $_instance = null;
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new Model_Post();
		}
		return self::$_instance;
	}
    
    public function get($param){
        $bind = array();
        $bind['dt_fuso'] = $_SESSION['tw_auth']['dt_fuso'];

        $sql = "SELECT
			p.co_post,
			p.nu_post,
            u.co_usuario,
			u.nu_usuario,
			u.no_email,
			u.no_usuario,
			p.tx_post,
			CONVERT_TZ(p.dt_cadastro,'+00:00',:dt_fuso) dt_cadastro,
			p.dt_cadastro dt_cadastro_utc,
			tp.qt_comentario,
			i.nu_imagem
		FROM
		tb_post p
		JOIN
		( 
            SELECT
			    p.co_post,
			    if(c.co_comentario is null,0,count(*)) qt_comentario
		    FROM
		    tb_post p
		    JOIN tb_usuario u
			    ON u.co_usuario = p.co_usuario ";

                if($param['tipo'] == "amigo"){
                    $sql .= " JOIN tb_seguidor s
	            ON s.co_usuario_seguido = u.co_usuario ";
                }

                $sql .= " LEFT JOIN tb_comentario c
	                ON c.co_post = p.co_post AND c.st_ativo = 1
                WHERE p.st_ativo = 1
	            AND u.st_ativo = 1 ";
                
                if($param['tipo'] == "rede"){
                    $sql .= " AND u.co_pais = :co_pais ";
                    $bind['co_pais'] = $_SESSION['tw_auth']['co_pais'];
                }

                if($param['tipo'] == "amigo"){
                    $sql .= " AND s.co_usuario_seguidor = :co_usuario_seguidor";
                    $bind['co_usuario_seguidor'] = $_SESSION['tw_auth']['co_usuario'];
                }
                
                if($param['tipo'] == "usuario"){
                    if($param['nu_usuario']){
                        $sql .= " AND u.nu_usuario = :nu_usuario";
                        $bind['nu_usuario'] = $param['nu_usuario'];
                    }else{
                        $sql .= " AND u.co_usuario = :co_usuario";
                        $bind['co_usuario'] = $param['co_usuario'];
                    }
                }

                if($param['tipo'] == "id"){
                    $sql .= " AND p.nu_post = :nu_post";
                    $bind['nu_post'] = $param['nu_post'];
                }
                
                if($param['uptime']){
                    $sql .= " AND p.dt_cadastro < :dt_cadastro";
                    $bind['dt_cadastro'] = $param['uptime'];
                }

                $sql .= " GROUP BY p.co_post
                    ORDER BY p.dt_cadastro desc
                    LIMIT 10 ) tp ON tp.co_post = p.co_post
        JOIN tb_usuario u ON u.co_usuario = p.co_usuario
        LEFT JOIN tb_imagem i ON i.co_imagem = u.co_imagem";
                
        $cache_id = "pst_" . md5(serialize($param) . serialize($bind));
        $cache = General::cache(10);
        General::setCacheID('pst',$cache_id);
        
        $rs = $cache->load($cache_id);
           
        if(!$rs){
            $rs = $this->getAdapter()->fetchAll($sql,$bind);
            $cache->save($rs,$cache_id);
        }
        
        unset($cache);
        $arIDPost = array();
        $arReturn = array();
        
        if(count($rs)){
            foreach($rs as $item){
                if($item['qt_comentario'] > 0){
                    $arIDPost[] = $item['co_post'];            
                }
            }
            
            $arComentario = array();
            if(count($arIDPost)){
                $arComentario = Model_Comentario::getInstance()->get(array(
                    "co_post" => $arIDPost,
                    "limit" => ($param['tipo'] == "id" ? 100 : 3)
                ));
            }
            
            foreach($rs as $item){
                $arReturn[$item['co_post']] = $item;
                $arReturn[$item['co_post']]['comentario'] = $arComentario[$item['co_post']];
            }
            
            unset($arComentario);
            unset($rs);
            unset($arIDPost);
            return $arReturn;
        }else{
            return array();
        }
    }
    
    public function inserir($param){
        $sql = "INSERT INTO tb_post (nu_post,co_usuario,tx_post,dt_cadastro) 
                VALUES (:nu_post,:co_usuario,:tx_post,UTC_TIMESTAMP())";
        
        $this->getAdapter()->prepare($sql)->execute($param);
        
        General::clearCache('pst');
    }
}
?>