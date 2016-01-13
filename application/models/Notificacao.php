<?php
class Model_Notificacao extends Zend_Db_Table {
	private static $_instance = null;
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new Model_Notificacao();
		}
		return self::$_instance;
	}
}
?>