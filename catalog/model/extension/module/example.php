<?php
class ModelExtensionModuleExample extends Model {
  // Загрузка настроек из базы данных
  public function LoadSettings() {
    return $this->config->get('module_example_status');
  }

  public function addProducts($data) {
  	$this->db->query("INSERT INTO `" . DB_PREFIX . "poster_product` SET name_im = '" . $this->db->escape($data['name_im']) . "', name_poster = '" . $this->db->escape($data['name_poster']) . "', id_product_im = '" . (int)$data['id_product_im'] . "', id_product_poster = '" . (int)$data['id_product_poster'] . "', cost_im = '" . (int)$data['cost_im'] . "', cost_poster = '" . (int)$data['cost_poster'] ."'");
  	return $this->db->getLastId();
    
  }


  public function getProduct($data) {
  	$getProduct_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "poster_product` WHERE id_product_im = '" . (int)$data['id_product_im'] . "'");
  	if ($getProduct_query->num_rows) {
  		$getProduct=$getProduct_query->row;
  		return $getProduct;
  	}
  	else
  	{
  		return false;
  	}   
  }



  

  
}
?>