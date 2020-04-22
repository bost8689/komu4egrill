<?php
class ControllerExtensionModuleOCdevSmartCart extends Controller {

  private $error              = array();
  static  $_module_version    = '3.0';
  static  $_module_name       = 'ocdev_smart_cart';

  public function index() {
    $data = array();

    // connect models array
    $models = array('setting/store', 'setting/setting', 'setting/extension', 'catalog/category', 'catalog/manufacturer', 'customer/customer_group', 'localisation/language', 'catalog/product', 'tool/image', 'extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));
    $this->document->setTitle($this->language->get('heading_name'));

    $styles = array('stylesheet.css');
    foreach ($styles as $style) {
      $this->document->addStyle('view/stylesheet/'.self::$_module_name.'/'.$style);
    }

    if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
      if (is_uploaded_file($this->request->files['import']['tmp_name'])) {
        $content = file_get_contents($this->request->files['import']['tmp_name']);
      }

      if (isset($content)) {
        $this->session->data['success'] = $this->language->get('text_success');
        $this->model_setting_setting->editSetting(self::$_module_name, unserialize($content));
        $this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true));
      } else {
        $this->session->data['success'] = $this->language->get('text_success');
        $this->model_setting_setting->editSetting(self::$_module_name, $this->request->post);
        $this->model_setting_setting->editSetting('module_' . self::$_module_name, array('module_' . self::$_module_name .'_status' => $this->request->post[self::$_module_name.'_form_data']['activate']));
        $this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true));
      }
    }

    $data['error_warning']                    = (isset($this->error['warning'])) ? $this->error['warning'] : '';
    $data['error_add_function_selector']      = (isset($this->error['add_function_selector'])) ? $this->error['add_function_selector'] : '';
    $data['error_add_id_selector']            = (isset($this->error['add_id_selector'])) ? $this->error['add_id_selector'] : '';
    $data['error_limit_cross_sell_products']  = (isset($this->error['limit_cross_sell_products'])) ? $this->error['limit_cross_sell_products'] : '';
    $data['error_limit_up_sell_products']     = (isset($this->error['limit_up_sell_products'])) ? $this->error['limit_up_sell_products'] : '';
    $data['error_heading']                    = (isset($this->error['heading'])) ? $this->error['heading'] : '';
    $data['error_call_button']                = (isset($this->error['call_button'])) ? $this->error['call_button'] : '';
    $data['error_call_button_vs_options']     = (isset($this->error['call_button_vs_options'])) ? $this->error['call_button_vs_options'] : '';
    $data['error_empty_text']                 = (isset($this->error['empty_text'])) ? $this->error['empty_text'] : '';
    $data['error_email_for_notification']     = (isset($this->error['email_for_notification'])) ? $this->error['email_for_notification'] : '';

    if (!empty($this->error)) {
      $data['error_warning'] = $this->language->get('error_warning');
    }

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    $data['heading_title'] = $this->language->get('heading_title');

    $data['breadcrumbs'] = array(
      0 => array(
        'text'      => $this->language->get('text_home'),
        'href'      => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], true),
        'separator' => false
      ),
      1 => array(
        'text'      => $this->language->get('text_module'),
        'href'      => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true),
        'separator' => ' :: '
      ),
      2 => array(
        'text'      => $this->language->get('heading_name'),
        'href'      => $this->url->link('extension/module/'.self::$_module_name, 'user_token='.$this->session->data['user_token'], true),
        'separator' => ' :: '
      )
    );

    $data['action']           = $this->url->link('extension/module/'.self::$_module_name, 'user_token='.$this->session->data['user_token'], true);
    $data['action_plus']      = $this->url->link('extension/module/'.self::$_module_name.'/edit_and_stay', 'user_token='.$this->session->data['user_token'], true);
    $data['export_settings_button'] = $this->url->link('extension/module/'.self::$_module_name.'/export_settings', 'user_token='.$this->session->data['user_token'], true);
    $data['import_settings_button'] = $this->url->link('extension/module/'.self::$_module_name.'/import_settings', 'user_token='.$this->session->data['user_token'], true);
    $data['cancel']           = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module', true);
    $data['admin_language']   = $this->config->get('config_admin_language');
    $data['_module_name']     = (string)self::$_module_name;
    $data['_module_version']  = (string)self::$_module_version;
    $data['user_token']            = $this->session->data['user_token'];
    $data['text_data']        = isset($this->request->post[self::$_module_name.'_text_data']) ? $this->request->post[self::$_module_name.'_text_data'] : $this->config->get(self::$_module_name.'_text_data');
    $data['form_data']        = isset($this->request->post[self::$_module_name.'_form_data']) ? $this->request->post[self::$_module_name.'_form_data'] : $this->config->get(self::$_module_name.'_form_data');

    $form_data = $data['form_data'];

    // cross-sell products
    $data['all_products'] = array();

    if (isset($this->request->post['product'])) {
      $products = $this->request->post['product'];
    } elseif (!empty($form_data['cross_sell_products'])) {
      $products = $form_data['cross_sell_products'];
    } else {
      $products = array();
    }

    if ($products) {
      foreach ($products as $product_id) {
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
          $data['all_products'][] = array(
            'product_id' => $product_info['product_id'],
            'name'       => $product_info['name']
          );
        }
      }
    }

    // cross-sell categories
    $data['all_categories'] = array();

    if (isset($this->request->post['category'])) {
      $categories = $this->request->post['category'];
    } elseif (!empty($form_data['cross_sell_categories'])) {
      $categories = $form_data['cross_sell_categories'];
    } else {
      $categories = array();
    }

    if ($categories) {
      foreach ($categories as $category_id) {
        $category_info = $this->model_catalog_category->getCategory($category_id);

        if ($category_info) {
          $data['all_categories'][] = array(
            'category_id' => $category_info['category_id'],
            'name'        => $category_info['name']
          );
        }
      }
    }

    // cross-sell manufacturers
    $data['all_manufacturers'] = array();

    if (isset($this->request->post['manufacturer'])) {
      $manufacturers = $this->request->post['manufacturer'];
    } elseif (!empty($form_data['cross_sell_manufacturers'])) {
      $manufacturers = $form_data['cross_sell_manufacturers'];
    } else {
      $manufacturers = array();
    }

    if ($manufacturers) {
      foreach ($manufacturers as $manufacturer_id) {
        $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);

        if ($manufacturer_info) {
          $data['all_manufacturers'][] = array(
            'manufacturer_id' => $manufacturer_info['manufacturer_id'],
            'name'            => $manufacturer_info['name']
          );
        }
      }
    }

    $default_store = array(0 => array('store_id' => 0, 'name' => $this->config->get('config_name').' (Default)'));

    $all_stores = array_merge($this->model_setting_store->getStores(), $default_store);

    $data['all_stores'] = array();

    foreach ($all_stores as $store) {
      $data['all_stores'][] = array(
        'store_id' => $store['store_id'],
        'name'     => $store['name']
      );
    }

    $data['all_customer_groups'] = array();

    foreach ($this->{'model_customer_customer_group'}->getCustomerGroups() as $customer_group) {
      $data['all_customer_groups'][] = array(
        'customer_group_id' => $customer_group['customer_group_id'],
        'name'              => $customer_group['name']
      );
    }

    $data['backgrounds'] = array();

    if ($this->get_background()) {
      foreach ($this->get_background() as $background) {
        $name_string = explode("/", $background);
        $name = array_pop($name_string);
        $data['backgrounds'][] = array(
          'src'  => $background,
          'name' => $name
        );
      }
    }

    if (isset($this->request->post[self::$_module_name . '_upsell_data'])) {
      $upsell_array = $this->request->post[self::$_module_name . '_upsell_data'];
    } elseif ($this->config->get(self::$_module_name . '_upsell_data')) {
      $upsell_array = $this->config->get(self::$_module_name . '_upsell_data');
    } else {
      $upsell_array = array();
    }

    $data['upsells'] = array();

    foreach ($upsell_array as $key => $upsell) {
      if (!isset($upsell_array[$key]['cart_products'])) {
        unset($upsell_array[$key]);
      }
    }

    foreach ($upsell_array as $upsell) {

      $cart_products = array();

      if(isset($upsell['cart_products'])) {
        foreach ($upsell['cart_products'] as $product_id) {
          $product_info = $this->model_catalog_product->getProduct($product_id);
          if ($product_info) {
            $cart_products[] = array(
              'product_id' => $product_info['product_id'],
              'name'       => $product_info['name']
           );
          }
        }
      }

      $upsell_products = array();

      if(isset($upsell['upsell_products'])) {
        foreach ($upsell['upsell_products'] as $product_id) {
          $product_info = $this->model_catalog_product->getProduct($product_id);
          if ($product_info) {
            $upsell_products[] = array(
              'product_id' => $product_info['product_id'],
              'name'       => $product_info['name']
            );
          }
        }
      }

      $data['upsells'][] = array(
        'cart_products'   => $cart_products,
        'upsell_products' => $upsell_products,
        'type'            => $upsell['type']
      );
    }

    // codev products
    $data['ocdev_products'] = $this->{'model_extension_module_'.self::$_module_name}->getOCdevCatalog();

    $data['languages']    = $this->model_localisation_language->getLanguages();
    $data['header']       = $this->load->controller('common/header');
    $data['column_left']  = $this->load->controller('common/column_left');
    $data['footer']       = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/'.self::$_module_name, $data));
  }

  public function history() {
    $data = array();

    // connect model data
    $models = array('extension/module/'.self::$_module_name, 'tool/image');
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));
    $page = (isset($this->request->get['page'])) ? $this->request->get['page']: 1;

    $data['histories'] = array();

    $results = $this->{'model_extension_module_'.self::$_module_name}->getSavedCartArray(($page - 1) * 10, 10);

    foreach ($results as $result) {

      // products in cart
      $result_products = array();

      $products = unserialize($result['cart_products']);

      foreach ($products as $product) {

        $image = $product['image'] ? $this->model_tool_image->resize($product['image'], 50, 50): $this->model_tool_image->resize("no_image.png", 50, 50);

        $option_data = array();

        foreach ($product['option'] as $option) {
          if ($option['type'] != 'file') {
            $value = $option['value'];
          } else {
            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

            if ($upload_info) {
              $value = $upload_info['name'];
            } else {
              $value = '';
            }
          }

          $option_data[] = array(
            'name'  => $option['name'],
            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20).'..' : $value)
         );
        }

        $result_products[] = array(
          'product_id' => $product['product_id'],
          'image'      => $image,
          'options'    => $option_data,
          'name'       => $product['name'],
          'quantity'   => $product['quantity'],
          'total'      => $this->currency->format($product['total'], $this->session->data['currency'], $this->currency->getValue($this->session->data['currency'])),
          'href'       => $this->url->link('catalog/product/edit', 'user_token='.$this->session->data['user_token'].'&product_id='.$product['product_id'], true)
        );
      }

      $data['histories'][] = array(
        'cart_id'               => $result['cart_id'],
        'products'              => $result_products,
        'ip'                    => $result['ip'],
        'email'                 => $result['email'],
        'date_added'            => $result['date_added']
      );
    }

    $history_total = $this->{'model_extension_module_'.self::$_module_name}->getTotalSavedCartArray();

    $pagination = new Pagination();
    $pagination->total = $history_total;
    $pagination->page = $page;
    $pagination->limit = 10;
    $pagination->url = $this->url->link('extension/module/'.self::$_module_name.'/history', 'user_token='.$this->session->data['user_token'].'&page={page}', true);

    $data['pagination'] = $pagination->render();

    $data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

    $this->response->setOutput($this->load->view('extension/module/'.self::$_module_name.'_history', $data));
  }

  public function delete_selected() {
    $json = array();

    // connect model data
    $models = array('extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $info = $this->{'model_extension_module_'.self::$_module_name}->getSavedCart((int)$this->request->get['delete']);

    if ($info) {
      $this->{'model_extension_module_'.self::$_module_name}->deleteSavedCart((int)$this->request->get['delete']);
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function delete_all() {
    $json = array();

    // connect model data
    $models = array('extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $this->{'model_extension_module_'.self::$_module_name}->deleteAllSavedCarts();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function delete_all_selected() {
    $json = array();

    // connect model data
    $models = array('extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    if (isset($this->request->request['selected'])) {
      foreach ($this->request->request['selected'] as $link_id) {
        $info = $this->{'model_extension_module_'.self::$_module_name}->getSavedCart((int)$link_id);

        if ($info) {
          $this->{'model_extension_module_'.self::$_module_name}->deleteSavedCart((int)$link_id);
        }
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function edit_and_stay() {
    $data = array();

    // connect models array
    $models = array('setting/setting');
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));
    $this->document->setTitle($this->language->get('heading_name'));

    if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {

      if (is_uploaded_file($this->request->files['import']['tmp_name'])) {
        $content = file_get_contents($this->request->files['import']['tmp_name']);
      }

      if (isset($content)) {
        $this->session->data['success'] = $this->language->get('text_success');
        $this->model_setting_setting->editSetting(self::$_module_name, unserialize($content));
        $this->response->redirect($this->url->link('extension/module/'.self::$_module_name, 'user_token='.$this->session->data['user_token'], true));
      } else {
        $this->session->data['success'] = $this->language->get('text_success');
        $this->model_setting_setting->editSetting(self::$_module_name, $this->request->post);
        $this->response->redirect($this->url->link('extension/module/'.self::$_module_name, 'user_token='.$this->session->data['user_token'], true));
      }
    } else {
      $this->index();
    }
  }

  public function install() {
    // connect language data
    $text_form = $this->load->language('extension/module/'.self::$_module_name);

    // connect model data
    $models = array('setting/setting', 'setting/extension', 'extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    // add permission
    $modules = array('extension/module/'.self::$_module_name);
    foreach ($modules as $module) {
      $this->model_user_user_group->addPermission($this->user->getId(), 'access', $module);
      $this->model_user_user_group->addPermission($this->user->getId(), 'modify', $module);
    }

    $this->{'model_extension_module_'.self::$_module_name}->createDBTables();

    // set default data
    $this->model_setting_setting->editSetting(self::$_module_name, array(
      self::$_module_name.'_text_data' => array(
        $this->config->get('config_admin_language') => array(
          'heading'                 => $this->language->get('default_heading'),
          'call_button'             => $this->language->get('default_call_button'),
          'call_button_vs_options'  => $this->language->get('default_call_button_vs_options'),
          'empty_text'              => $this->language->get('default_empty_text')
        ),
      ),
      self::$_module_name.'_form_data' => array(
        'activate'                               => '1',
        'add_function_selector'                  => 'cart.add',
        'add_id_selector'                        => 'button-cart',
        'hide_main_img'                          => '1',
        'main_image_width'                       => '80',
        'main_image_height'                      => '80',
        'hide_sub_img'                           => '1',
        'sub_images_width'                       => '100',
        'sub_images_height'                      => '100',
        'limit_cross_sell_products'              => '12',
        'new_saved_cart_admin_alert'             => '0',
        'email_for_notification'                 => $this->config->get('config_email'),
        'hide_product_addto_cart_button'         => '0',
        'hide_up_sell_sub_img'                   => '1',
        'sub_up_sell_images_width'               => '100',
        'sub_up_sell_images_height'              => '100',
        'limit_up_sell_products'                 => '12',
        'hide_up_sell_product_addto_cart_button' => '0',
        'hide_up_sell_product_price'             => '0',
        'hide_save_cart_button'                  => '1',
        'hide_cart_weight'                       => '0',
        'hide_coupon'                            => '1',
        'hide_voucher'                           => '1',
        'hide_reward'                            => '1',
        'hide_shipping'                          => '1',
        'hide_product_price'                     => '0',
        'hide_product_model'                     => '0',
        'hide_product_ean'                       => '0',
        'hide_product_jan'                       => '0',
        'hide_product_isbn'                      => '0',
        'hide_product_mpn'                       => '0',
        'hide_product_location'                  => '0',
        'hide_product_reward'                    => '0',
        'hide_product_stock'                     => '0',
        'hide_product_option'                    => '1',
        'hide_product_tax'                       => '0',
        'check'                                  => '0',
        'style_beckground'                       => 'bg_7.png',
        'background_opacity'                     => '8',
        'marketing_type'                         => '1',
        'randomize'                              => '1',
        'cross_sell_categories'                  => array(),
        'cross_sell_manufacturers'               => array(),
        'cross_sell_products'                    => array(),
        'front_module_name'                      => str_replace(array('<b>','</b>'), "", $text_form['heading_name']),
        'front_module_version'                   => (string)self::$_module_version
      )
    ));

    if (!in_array(self::$_module_name, $this->model_setting_extension->getInstalled('module'))) {
      $this->model_setting_extension->install('module', self::$_module_name);
    }

    $this->model_setting_setting->editSetting('module_' . self::$_module_name, array('module_' . self::$_module_name . '_status' => 1));

    $this->session->data['success'] = $text_form['text_success_install'];

    $this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'], true));
  }

  public function uninstall() {

    // connect model data
    $models = array('setting/setting', 'setting/extension', 'extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $this->{'model_extension_module_'.self::$_module_name}->deleteDBTables();
    $this->model_setting_extension->uninstall('module', self::$_module_name);
    $this->model_setting_setting->deleteSetting(self::$_module_name);
    $this->model_setting_setting->deleteSetting('module_' . self::$_module_name);
  }

  private function validate() {

    $text_form = $this->load->language('extension/module/'.self::$_module_name);

    if (!$this->user->hasPermission('modify', 'extension/module/'.self::$_module_name)) {
      $this->error['warning'] = $text_form['error_permission'];
    }

    foreach ($this->request->post[self::$_module_name.'_text_data'] as $language_code => $value) {
      if ((utf8_strlen($value['heading']) < 1) || (utf8_strlen($value['heading']) > 255)) {
        $this->error['heading'][$language_code] = $this->language->get('error_for_all_field');
      }
      if ((utf8_strlen($value['call_button']) < 1) || (utf8_strlen($value['call_button']) > 255)) {
        $this->error['call_button'][$language_code] = $this->language->get('error_for_all_field');
      }
      if ((utf8_strlen($value['call_button_vs_options']) < 1) || (utf8_strlen($value['call_button_vs_options']) > 255)) {
        $this->error['call_button_vs_options'][$language_code] = $this->language->get('error_for_all_field');
      }
      if ((utf8_strlen($value['empty_text']) < 1) || (utf8_strlen($value['empty_text']) > 5000)) {
        $this->error['empty_text'][$language_code] = $this->language->get('error_for_all_field');
      }
    }

    foreach ($this->request->post[self::$_module_name.'_form_data'] as $main_key => $field) {
      if (empty($field) && $main_key == "add_function_selector") {
        $this->error['add_function_selector'] = $this->language->get('error_for_all_field');
      }

      if (empty($field) && $main_key == "add_id_selector") {
        $this->error['add_id_selector'] = $this->language->get('error_for_all_field');
      }

      if (empty($field) && $main_key == "limit_cross_sell_products") {
        $this->error['limit_cross_sell_products'] = $this->language->get('error_for_all_field');
      }

      if (empty($field) && $main_key == "limit_up_sell_products") {
        $this->error['limit_up_sell_products'] = $this->language->get('error_for_all_field');
      }

      if (empty($field) && $main_key == "email_for_notification") {
        $this->error['email_for_notification'] = $this->language->get('error_for_all_field');
      }
    }

    return (!$this->error) ? TRUE : FALSE;
  }

  private function cacheCheck($dir) {
    return !is_dir(DIR_IMAGE.$dir) || !is_writable(DIR_IMAGE.$dir) ? false : true;
  }

  public function get_background() {

    $text_form = $this->load->language('extension/module/'.self::$_module_name);

    $check_dir = self::$_module_name.'/background';

    $backgrounds = array();

    if(!$this->cacheCheck($check_dir)) {
      $this->error['warning'] = $text_form['error_cache_folder'];
    } else {
      $dir = opendir(DIR_IMAGE.$check_dir);

      while (($file = readdir($dir)) !== FALSE) {
        if (in_array(substr(strrchr($file, '.'), 1), array('png', 'jpg'))) {
          $backgrounds[] = ('/image/'.$check_dir.'/'.$file);
        }
      }

      closedir($dir);

      return $backgrounds;
    }
  }

  public function export_settings() {
    // connect models array
    $models = array('setting/setting', 'setting/store');
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $module_settings = $this->model_setting_setting->getSetting(self::$_module_name);

    $this->response->addheader('Pragma: public');
    $this->response->addheader('Expires: 0');
    $this->response->addheader('Content-Description: File Transfer');
    $this->response->addheader('Content-Type: application/octet-stream');
    $this->response->addheader('Content-Disposition: attachment; filename='.self::$_module_name.'_'.date("Y-m-d H:i:s", time()).'.txt');
    $this->response->addheader('Content-Transfer-Encoding: binary');

    $this->response->setOutput(serialize($module_settings));
  }
}
?>
