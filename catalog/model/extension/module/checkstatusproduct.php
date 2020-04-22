<?php
class ModelExtensionModuleCheckstatusproduct extends Model {
  // Загрузка настроек из базы данных
  public function LoadSettings() {
    return $this->config->get('module_checkstatusproduct_status');
  }

  public function addStatusProduct($data) {
    $this->db->query("INSERT INTO `" . DB_PREFIX . "poster_checkstatusproduct` SET im_order_id = '" . (int)$data['im_order_id'] . "', poster_order_id = '" . (int)$data['poster_order_id'] . "', poster_transaction_id = '" . (int)$data['poster_transaction_id'] . "', poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "', status_sms = '" . $this->db->escape($data['status_sms']) ."'");
    return $this->db->getLastId();    
  }


  public function getStatusProduct($data) {
  	$getStatusProduct_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "poster_checkstatusproduct` WHERE im_order_id = '" . (int)$data['im_order_id'] . "'");
  	if ($getStatusProduct_query->num_rows) {
  		$getStatusProduct=$getStatusProduct_query->row;
  		return $getStatusProduct;
  	}
  	else
  	{
  		return false;
  	}   
  }

  public function getPosterStatusOrder($data) {
    $getPosterStatusOrder_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "poster_checkstatusproduct` WHERE poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "'");
    // $log = New Log('checkstatusproduct.txt');
    // $log -> write($getPosterStatusOrder_query);
    if ($getPosterStatusOrder_query->num_rows) {
      $getPosterStatusOrder=$getPosterStatusOrder_query->rows;
      return $getPosterStatusOrder;
    }
    else
    {
      return false;
    }   
  }


  public function setStatusOrder($data) {
    if (isset($data['poster_transaction_id'])) {
      $setStatusOrder_query = $this->db->query("UPDATE " . DB_PREFIX . "poster_checkstatusproduct SET poster_transaction_id = '" . $this->db->escape($data['poster_transaction_id']) . "' WHERE id = '" . (int)$data['id'] . "'");
    }
    elseif(isset($data['poster_status_order'])){
      $setStatusOrder_query = $this->db->query("UPDATE " . DB_PREFIX . "poster_checkstatusproduct SET poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "' WHERE id = '" . (int)$data['id'] . "'");
    }
        
    // $log = New Log('checkstatusproduct.txt');
    // $log -> write($setStatusOrder_query);
    /*if ($getPosterStatusOrder_query->num_rows) {
      $getPosterStatusOrder=$getPosterStatusOrder_query->rows;
      return $getPosterStatusOrder;
    }
    else
    {
      return false;
    }   */
  }


  


}
?>