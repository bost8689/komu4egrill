<?php
class ModelExtensionModuleCheckstatusproduct extends Model {
  // Загрузка настроек из базы данных
  public function LoadSettings() {
    return $this->config->get('module_checkstatusproduct_status');
  }

  //добавляю продукт 
  public function addStatusProduct($data) {
    date_default_timezone_set('Asia/Yekaterinburg');
    
    $this->db->query("INSERT INTO `" . DB_PREFIX . "poster_checkstatusproduct` SET im_order_id = '" . (int)$data['im_order_id'] . "', poster_order_id = '" . (int)$data['poster_order_id'] . "', poster_transaction_id = '" . (int)$data['poster_transaction_id'] . "', poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "', status_sms = '" . $this->db->escape($data['status_sms']) . "', date_status = '" . date("Y-m-d H:i:s") ."'");
    return $this->db->getLastId();    
  }

  //меняю статус продукта
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

  //получить по статусу продукт
  public function getPosterStatusOrder($data) {
    
    if (isset($data['poster_status_order'])) {
      $getPosterStatusOrder_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "poster_checkstatusproduct` WHERE poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "'");
    }
    elseif(isset($data['im_order_id'])){
      $getPosterStatusOrder_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "poster_checkstatusproduct` WHERE im_order_id = '" . (int)$data['im_order_id'] . "'");
    }
    

    
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

  //меняю статус продукта
  public function setStatusOrder($data) {
    // $log = New Log('setStatusOrder.txt');
    // $log -> write('setStatusOrder запускается');
    // return;
    if (isset($data['poster_transaction_id'])) {
      $setStatusOrder_query = $this->db->query("UPDATE " . DB_PREFIX . "poster_checkstatusproduct SET poster_transaction_id = '" . $this->db->escape($data['poster_transaction_id']) . "' WHERE id = '" . (int)$data['id'] . "'");
    }
    elseif(isset($data['poster_status_order']) && isset($data['id'])){
      $setStatusOrder_query = $this->db->query("UPDATE " . DB_PREFIX . "poster_checkstatusproduct SET poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "' WHERE id = '" . (int)$data['id'] . "'");
    }
    elseif(isset($data['poster_status_order']) && isset($data['im_order_id'])){
      $setStatusOrder_query = $this->db->query("UPDATE " . DB_PREFIX . "poster_checkstatusproduct SET poster_status_order = '" . $this->db->escape($data['poster_status_order']) . "' WHERE im_order_id = '" . (int)$data['im_order_id'] . "'");
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