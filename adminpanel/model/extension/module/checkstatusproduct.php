<?php
class ModelExtensionModuleCheckstatusproduct extends Model {

  // Запись настроек в базу данных
  public function SaveSettings() {
    $this->load->model('setting/setting');
    // var_dump($this->request->post);
    // return;
    $this->model_setting_setting->editSetting('module_checkstatusproduct', $this->request->post);
  }

  // Загрузка настроек из базы данных
  public function LoadSettings() {
    return $this->config->get('module_checkstatusproduct_status');
  }

}
?>