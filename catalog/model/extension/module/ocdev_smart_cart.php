<?php
class ModelExtensionModuleOCdevSmartCart extends Model {

    static  $_module_version    = '2.2';
    static  $_module_name       = 'ocdev_smart_cart';

	public function getCrossSellProducts($data = array()) {
		$customer_group_id = ($this->customer->isLogged()) ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id');

		$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$customer_group_id . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";

		if (!empty($data['filter_sub_category'])) {
			$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
		} else {
			$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id IN (" . implode(',', $data['filter_category_id']) . ")";
			} else {
				$sql .= " AND p2c.category_id IN (" . implode(',', $data['filter_category_id']) . ")";
			}
		}

		if (!empty($data['products_id'])) {
			$products_id = explode(',', $data['products_id']);

			foreach ($products_id as $product_id) {
				$implode[] = (int)$product_id;
			}

			$sql .= " AND p.product_id IN (" . implode(',', $implode) . ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id IN (" . implode(',', $data['filter_manufacturer_id']) . ")";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
			'rating',
			'p.sort_order',
			'p.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
		}

		$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['end'];

		$product_data = array();

		$query = $this->db->query($sql);

		$this->load->model('catalog/product');

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getCrossSellTotalProducts($data = array()) {
		$customer_group_id = ($this->customer->isLogged()) ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id');

		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total";

		if (!empty($data['filter_sub_category'])) {
			$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
		} else {
			$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id IN (" . implode(',', $data['filter_category_id']) . ")";
			} else {
				$sql .= " AND p2c.category_id IN (" . implode(',', $data['filter_category_id']) . ")";
			}
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id IN (" . implode(',', $data['filter_manufacturer_id']) . ")";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function addSavedCart($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "smca_saved_carts SET cart_products = '" . $this->db->escape($data['cart']) . "', totals = '" . $data['totals'] . "', visitor = '" . $data['visitor'] . "', email = '" . $data['email'] . "', ip = '" . $data['ip'] . "', date_added = NOW()");
	}

	public function updateSavedCart($data) {
		$this->db->query("UPDATE " . DB_PREFIX . "smca_saved_carts SET cart_products = '" . $this->db->escape($data['cart']) . "', totals = '" . $data['totals'] . "', email = '" . $data['email'] . "' WHERE visitor = '" . $data['visitor'] . "'");
	}

	public function getSavedCart($visitor_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "smca_saved_carts WHERE visitor = '" . $visitor_id . "'");

		return $query->row;
	}
}
?>
