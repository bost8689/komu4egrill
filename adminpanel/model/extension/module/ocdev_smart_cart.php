<?php
class ModelExtensionModuleOCdevSmartCart extends Model {

	static  $_module_version    = '2.2';
	static  $_module_name       = 'ocdev_smart_cart';

	public function getSavedCart($cart_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM `".DB_PREFIX."smca_saved_carts` WHERE cart_id = '".(int)$cart_id."'");

		return $query->row;
	}

	public function deleteSavedCart($cart_id) {
		$this->db->query("DELETE FROM `".DB_PREFIX."smca_saved_carts` WHERE cart_id = '".(int)$cart_id."'");
	}

	public function deleteAllSavedCarts() {
		$this->db->query("DELETE FROM `".DB_PREFIX."smca_saved_carts`");
	}

	public function getSavedCartArray($start = 0, $limit = 10) {

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT * FROM `".DB_PREFIX."smca_saved_carts` ORDER BY date_added DESC LIMIT ".(int)$start.",".(int)$limit);

		return $query->rows;
	}

	public function getTotalSavedCartArray() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."smca_saved_carts`");

		return $query->row['total'];
	}

	public function createDBTables() {
		$sql  = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."smca_saved_carts` ( ";
		$sql .= "`cart_id` int(11) NOT NULL AUTO_INCREMENT, ";
		$sql .= "`visitor` text COLLATE utf8_general_ci NOT NULL, ";
		$sql .= "`email` text COLLATE utf8_general_ci NOT NULL, ";
		$sql .= "`cart_products` text COLLATE utf8_general_ci NOT NULL, ";
		$sql .= "`totals` decimal(15,4) NOT NULL, ";
		$sql .= "`ip` varchar(40) COLLATE utf8_general_ci NOT NULL, ";
		$sql .= "`date_added` datetime NOT NULL, ";
		$sql .= "PRIMARY KEY (`cart_id`) ";
		$sql .= ") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci AUTO_INCREMENT=1 ;";

		$this->db->query($sql);
	}

	public function deleteDBTables() {
		$sql  = "DROP TABLE IF EXISTS `".DB_PREFIX."smca_saved_carts`;";

		$this->db->query($sql);
	}

	public function getOCdevCatalog() {
		$catalog = array();
		return $catalog;
	}
}
