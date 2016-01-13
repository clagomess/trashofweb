<?php
class Model_Comentario extends Zend_Db_Table {
	private static $_instance = null;
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new Model_Comentario();
		}
		return self::$_instance;
	}
    
    public function get($param){
        $this->getAdapter()->exec("set @row_num = 0");
        $this->getAdapter()->exec("set @co_post = 0");
        $bind = array();
        $bind['dt_fuso'] = $_SESSION['tw_auth']['dt_fuso'];
        $bind['limit'] = ($param['limit'] ? $param['limit'] : 3);

        $sql = "SELECT
	        c.co_post,
            u.co_usuario,
	        u.nu_usuario,
	        u.no_email,
	        u.no_usuario,
	        i.nu_imagem,
	        c.nu_comentario,
	        CONVERT_TZ(c.dt_cadastro,'+00:00',:dt_fuso) dt_cadastro,
	        c.dt_cadastro dt_cadastro_utc,
	        c.tx_comentario
        FROM tb_comentario c
        JOIN (
	        SELECT 
	        co_comentario
	        FROM (
		        SELECT
		        co_post,
		        co_comentario,
		        if(@co_post <> co_post,@row_num := 0,@row_num := @row_num + 1) row_num,
		        (@co_post := co_post) co_post_ctr
		        FROM 
		        (SELECT * FROM tb_comentario ORDER BY co_comentario desc) c
		        WHERE ";

                if($param['co_post']){
                    if(!is_array($param['co_post']) || count($param['co_post']) == 1){
                        $sql .= " co_post = :co_post and ";
                        $bind['co_post'] = current($param['co_post']);
                    }else{
                        $sql .= " co_post IN (".implode(',',$param['co_post']).") and ";
                    }
                }
        
                if($param['nu_post']){
                    $sql .= " co_post = (SELECT co_post FROM tb_post 
                              WHERE nu_post = :nu_post) and ";
			        $bind['nu_post'] = $param['nu_post'];
                }
        
                $sql .= " st_ativo = 1
		        ORDER BY co_post
	        ) c
	        WHERE c.row_num < :limit
        ) tc ON tc.co_comentario = c.co_comentario
        JOIN tb_usuario u ON u.co_usuario = c.co_usuario
        LEFT JOIN tb_imagem i ON i.co_imagem = u.co_imagem
        ORDER BY c.co_post, c.dt_cadastro ASC";
        
        $cache_id = "cmt_" . md5(serialize($param) . serialize($bind));
        General::setCacheID('cmt',$cache_id);
        $cache = General::cache(10);
        $rs = $cache->load($cache_id);
        
        if(!$rs){
            $rs = $this->getAdapter()->fetchAll($sql,$bind);
            $cache->save($rs, $cache_id);
        }
        
        $arReturn = array();
        if(count($rs)){
            foreach($rs as $item){
                $arReturn[$item['co_post']][] = $item;
            }
            return $arReturn;
        }else{
            return array();
        }
    }
    
    public function inserir($param){
        $sql = "INSERT INTO tb_comentario (
                    nu_comentario,co_usuario,co_post,
                    tx_comentario,dt_cadastro
                )
                SELECT :nu_comentario, :co_usuario, co_post, 
                    :tx_comentario, UTC_TIMESTAMP() 
                FROM tb_post WHERE nu_post = :nu_post";
        $this->getAdapter()->prepare($sql)->execute($param);
        General::clearCache('cmt');
    }
}
?>