<?php
/**
 * Clase en donde se guardan las transacciones
 */

class Epayco_agregadorOrder extends ObjectModel{
	public $id;
	public $id_payco;
	public $order_id;
	public $order_stock_restore;
	public $order_stock_discount;
	public $order_status;
	
	public static $definition = array(
		'table' => _DB_PREFIX_.'payco_agregador',
		'primary' => 'id',
		'multilang' => false,
		'fields' => array(
				'id' => array('type' => self::TYPE_INT, 'required' => false),
				'id_payco' => array('type' => self::TYPE_INT, 'required' => false),
				'order_id' => array('type' => self::TYPE_INT, 'required' => false),
				'order_stock_restore' => array('type' => self::TYPE_INT, 'required' => false),
				'order_stock_discount' => array('type' => self::TYPE_INT, 'required' => false),
				'order_status' => array('type' => self::TYPE_STRING, 'required' => false)
		)
	);
	
	/**
	 * Guarda el registro de una oden
	 * @param int $orderId
	 * @param array $stock
	 */
	public static function create($orderId, $stock)
	{
		
		$db = Db::getInstance();
			$result = $db->execute('
			INSERT INTO `'.Epayco_agregadorOrder::$definition['table'].'`
			( `order_id`, `order_stock_restore` )
			VALUES
			("'.intval($orderId).'","'.$stock.'")');
		return $result;
	}


	/**
	 * Consultar si existe el registro de una oden
	 * @param int $orderId
	 */	
	public static function ifExist($orderId)
	{
		$sql = 'SELECT COUNT(*) FROM '.Epayco_agregadorOrder::$definition['table'].' WHERE order_id ='.$orderId;
		
		if (\Db::getInstance()->getValue($sql) > 0)
			return true;
		return false;
	}

	/**
	 * Consultar si a una orden ya se le descconto el stock
	 * @param int $orderId
	 */	
	public static function ifStockDiscount($orderId)
	{	
		$db = Db::getInstance();
		$result = $db->getRow('
			SELECT `order_stock_discount` FROM `'.Epayco_agregadorOrder::$definition['table'].'`
			WHERE `order_id` = "'.intval($orderId).'"');
	
		if ($result === false || !is_array($result) || !isset($result["order_stock_discount"])) {
        	//return false; // No se encontró el registro o no tiene el campo
    	}

    	return intval($result["order_stock_discount"]) != 0 ? true : false;
		
	}

	/**
	 * Actualizar que ya se le descontó el stock a una orden
	 * @param int $orderId
	 */	
	public static function updateStockDiscount($orderId)
	{
		$db = Db::getInstance();
		$result = $db->update('payco_agregador', array('order_stock_discount'=>1), 'order_id = '.(int)$orderId );

		return $result ? true : false;
	}
	
	/**
	 * Crear la tabla en la base de datos.
	 * @return true or false
	 */
	public static function setup()
	{
		$sql = array();
		$sql[] = 'CREATE TABLE IF NOT EXISTS `'.Epayco_agregadorOrder::$definition['table'].'` (
		    `id` int(11) NOT NULL AUTO_INCREMENT,
		    `id_payco` INT(11) NULL,
		    `order_id` INT NULL,
		    `order_stock_restore` INT NULL,
		    `order_stock_discount` INT NULL,
		    `order_status` TEXT NULL,
		    PRIMARY KEY  (`id`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

		foreach ($sql as $query) {
		    if (Db::getInstance()->execute($query) == false) {
		        return false;
		    }
		}
	}

	/**
	 * Borra la tabla en la base de datos.
	 * @return true or false
	 */
	public static function remove(){
		$sql = array(
				'DROP TABLE IF EXISTS '.Epayco_agregadorOrder::$definition['table']
		);

		foreach ($sql as $query) {
		    if (Db::getInstance()->execute($query) == false) {
		        return false;
		    }
		}
	}
}