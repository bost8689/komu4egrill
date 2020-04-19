<?php
class ControllerExtensionModuleOCdevSmartCart extends Controller {

  static $_module_version = '3.0';
  static $_module_name    = 'ocdev_smart_cart';

  public function index() {
    $data = array();

    // connect models array
    $models = array('catalog/product', 'tool/image', 'tool/upload', 'setting/extension', 'extension/module/'.self::$_module_name);
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name), $this->config->get(self::$_module_name.'_text_data'), $this->config->get(self::$_module_name.'_form_data'));

    $text_data  = (array)$this->config->get(self::$_module_name.'_text_data');
    $form_data  = (array)$this->config->get(self::$_module_name.'_form_data');

    $language_code = $this->session->data['language'];

    if (isset($text_data[$language_code])) {
      $data['heading_title'] = $text_data[$language_code]['heading'];
      $data['text_empty']   = html_entity_decode($text_data[$language_code]['empty_text'], ENT_QUOTES, 'UTF-8');
    }

    if (isset($this->request->request['remove'])) {
      $this->cart->remove($this->request->request['remove']);
      unset($this->session->data['vouchers'][$this->request->request['remove']]);
    }

    if (isset($this->request->request['update'])) {
      $this->cart->update($this->request->request['update'], $this->request->request['quantity']);
    }

    if (isset($this->request->request['add'])) {
      $this->cart->add($this->request->request['add'], $this->request->request['quantity']);
    }

    $data['button_cart'] = $this->language->get('button_cart');
    $data['checkout_link'] = $this->url->link('checkout/checkout', '', true);
    $data['save_cart_email'] = $this->customer->isLogged() ? (string)$this->customer->getEmail() : "";

    $points = $this->customer->getRewardPoints();
    $data['text_reward_title'] = sprintf($this->language->get('text_reward_title_heading'), $points);

    // cart products
    if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
      $data['error_stock'] = $this->language->get('error_stock');
    } elseif (isset($this->session->data['error'])) {
      $data['error_stock'] = $this->session->data['error'];

      unset($this->session->data['error']);
    } else {
      $data['error_stock'] = '';
    }

    $data['products'] = array();

    foreach ($this->cart->getProducts() as $product) {
      $product_total = 0;

      foreach ($this->cart->getProducts() as $product_2) {
        if ($product_2['product_id'] == $product['product_id']) {
          $product_total += $product_2['quantity'];
        }
      }

      $product_info = $this->model_catalog_product->getProduct($product['product_id']);

      if ($product_info['quantity'] <= 0) {
        $stock_text = $product_info['stock_status'];
      } elseif ($this->config->get('config_stock_display')) {
        $stock_text = $product_info['quantity'];
      } else {
        $stock_text = $this->language->get('text_instock');
      }

      $image = ($product['image']) ? $this->model_tool_image->resize($product['image'], $form_data['main_image_width'], $form_data['main_image_height']) : $this->model_tool_image->resize("no_image.jpg", $form_data['main_image_width'], $form_data['main_image_height']);

      $option_data = array();

      foreach ($product['option'] as $option) {
        if ($option['type'] != 'file') {
          $value = $option['value'];
        } else {
          $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
          $value = ($upload_info) ? $upload_info['name'] : '';
        }

        $option_data[] = array(
          'name'  => $option['name'],
          'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20).'..' : $value)
        );
      }

      // display price
      $price = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;

      // display tax
      if ($this->config->get('config_tax')) {
        $tax = $this->currency->format((float)$product['price'], $this->session->data['currency']);
        $tax_total = $this->currency->format(($product['price'] * $product['quantity']), $this->session->data['currency']);
      } else {
        $tax = false;
        $tax_total = false;
      }

      // display unit total
      $total = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format(($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity']), $this->session->data['currency']) : false;

      $data['products'][] = array(
        'key'         => $product['cart_id'],
        'product_id'  => $product['product_id'],
        'thumb'       => $image,
        'name'        => $product['name'],
        'model'       => $product['model'],
        'ean'         => $product_info['ean'],
        'jan'         => $product_info['jan'],
        'isbn'        => $product_info['isbn'],
        'mpn'         => $product_info['mpn'],
        'location'    => $product_info['location'],
        'option'      => $option_data,
        'quantity'    => $product['quantity'],
        'stock_text'  => $stock_text,
        'stock'       => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
        'reward'      => $product['reward'] ? $product['reward'] : '',
        'price'       => $price,
        'tax'         => $tax,
        'tax_total'   => $tax_total,
        'total'       => $total,
        'href'        => $this->url->link('product/product', 'product_id='.$product['product_id'], true)
      );
    }

    // totals
    $total_data = array();
    $total = 0;
    $taxes = $this->cart->getTaxes();

    // display prices
    if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
      $sort_order = array();

      $results = $this->model_setting_extension->getExtensions('total');

      foreach ($results as $key => $value) {
        $sort_order[$key] = $this->config->get('total_' . $value['code'].'_sort_order');
      }

      array_multisort($sort_order, SORT_ASC, $results);

      foreach ($results as $result) {
        if ($this->config->get('total_' . $result['code'].'_status')) {
          $this->load->model('extension/total/'.$result['code']);

          $this->{'model_extension_total_'.$result['code']}->getTotal(array('totals' => &$total_data, 'total' => &$total, 'taxes' => &$taxes));
        }

        $sort_order = array();

        foreach ($total_data as $key => $value) {
          $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $total_data);
      }

      foreach ($total_data as $value) {
        if ($value['code'] == 'total') {
          $data['total'] = $this->currency->format($value['value'], $this->session->data['currency']);
        }
      }
    }

    $data['cart_weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));

    // cross-sell && up-sell products
    $data['cross_sell_products'] = array();

    if ($form_data['marketing_type'] == 2) {

      $data['m_hide_sub_img'] = $data['hide_up_sell_sub_img'] ? $data['hide_up_sell_sub_img'] : '';
      $data['m_hide_product_price'] = $data['hide_up_sell_product_price'] ? $data['hide_up_sell_product_price'] : '';
      $data['m_hide_product_addto_cart_button'] = $data['hide_up_sell_product_addto_cart_button'] ? $data['hide_up_sell_product_addto_cart_button'] : '';

      $upsell_data = $this->config->get(self::$_module_name.'_upsell_data');

      $products_in_cart = array();

      foreach ($this->cart->getProducts() as $product) {
        $products_in_cart[] = $product['product_id'];
      }

      $upsell_products = array();
      if (!empty($upsell_data))
      foreach ($upsell_data as $result) {

        if ($result['cart_products'] && $result['type']) {

          foreach ($result['cart_products'] as $pr_id) {

            if (in_array($pr_id, $products_in_cart)) {

              if ($result['type'] == 1) {
                $related_products = $this->model_catalog_product->getProductRelated($pr_id);

                foreach ($related_products as $upsel_product) {
                  $upsell_products[] = $upsel_product['product_id'];
                }
              }

              if ($result['type'] == 2 && $result['upsell_products']) {
                foreach ($result['upsell_products'] as $upsel_product) {
                  $upsell_products[] = $upsel_product;
                }
              }

              if ($result['type'] == 3 && $result['upsell_products']) {
                $related_products = $this->model_catalog_product->getProductRelated($pr_id);

                foreach ($related_products as $upsel_product) {
                  $upsell_products[] = $upsel_product['product_id'];
                }

                foreach ($result['upsell_products'] as $upsel_product) {
                  $upsell_products[] = $upsel_product;
                }
              }

              $upsell_products = array_unique($upsell_products);
              $upsell_products = array_slice($upsell_products, 0, $form_data['limit_up_sell_products']);
            }
          }
        }
      }

      $data_x = array(
        'sort'        => 'p.date_added',
        'order'       => 'DESC',
        'start'       => isset($this->request->request['start']) ? (int)$this->request->request['start'] : 0,
        'end'         => isset($this->request->request['end']) ? (int)$this->request->request['end'] : 3,
        'products_id' => implode(',', $upsell_products)
      );

      $data['ajax_all_products'] = $upsell_products ? count($upsell_products) : 0;

      $ajax_products = $this->{'model_extension_module_'.self::$_module_name}->getCrossSellProducts($data_x);

      foreach($ajax_products as $product) {
        if (in_array($product['product_id'], $upsell_products)) {

          $image = ($product['image']) ? $this->model_tool_image->resize($product['image'], $form_data['sub_up_sell_images_width'], $form_data['sub_up_sell_images_height']) : $this->model_tool_image->resize("no_image.jpg", $form_data['sub_up_sell_images_width'], $form_data['sub_up_sell_images_height']);
          $price = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
          $special = ((float)$product['special']) ? $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;

          $data['cross_sell_products'][] = array(
            'product_id'    => $product['product_id'],
            'thumb'         => $image,
            'name'          => $product['name'],
            'price'         => $price,
            'special'       => $special,
            'href'          => $this->url->link('product/product', 'product_id='.$product['product_id'], true)
          );
        }
      }

      if ($form_data['randomize']) {
        shuffle($data['cross_sell_products']);
      }

    } else {

      $data['m_hide_sub_img'] = $data['hide_sub_img'] ? $data['hide_sub_img'] : '';
      $data['m_hide_product_price'] = $data['hide_product_price'] ? $data['hide_product_price'] : '';
      $data['m_hide_product_addto_cart_button'] = $data['hide_product_addto_cart_button'] ? $data['hide_product_addto_cart_button'] : '';

      if (!empty($form_data['check']) && $form_data['check'] == 3) {

        $products = array();

        if (isset($form_data['cross_sell_products'])) {
          foreach ($form_data['cross_sell_products'] as $product_id) {
            $products[] = $product_id;
          }
        }

        $data_x = array(
          'sort'        => 'p.date_added',
          'order'       => 'DESC',
          'start'       => isset($this->request->request['start']) ? (int)$this->request->request['start'] : 0,
          'end'         => isset($this->request->request['end']) ? (int)$this->request->request['end'] : 3,
          'products_id' => implode(',', $products)
        );

        $data['ajax_all_products'] = $form_data['cross_sell_products'] ? count($form_data['cross_sell_products']) : 0;

        $ajax_products = $this->{'model_extension_module_'.self::$_module_name}->getCrossSellProducts($data_x);

        foreach($ajax_products as $product) {
          if (in_array($product['product_id'], $products)) {

            $image = ($product['image']) ? $this->model_tool_image->resize($product['image'], $form_data['sub_images_width'], $form_data['sub_images_height']) : $this->model_tool_image->resize("no_image.jpg", $form_data['sub_images_width'], $form_data['sub_images_height']);
            $price = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
            $special = ((float)$product['special']) ? $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;

            $data['cross_sell_products'][] = array(
              'product_id'    => $product['product_id'],
              'thumb'         => $image,
              'name'          => $product['name'],
              'price'         => $price,
              'special'       => $special,
              'href'          => $this->url->link('product/product', 'product_id='.$product['product_id'], true)
            );
          }
        }

      } else {

        if ($form_data['check']) {

          if ($form_data['check'] == 1) {
            $data_x = array(
              'start'               => isset($this->request->request['start']) ? (int)$this->request->request['start'] : 0,
              'end'                 => isset($this->request->request['end']) ? (int)$this->request->request['end'] : 3,
              'filter_category_id'  => (!empty($form_data['cross_sell_categories']) && $form_data['check'] == 1) ? $form_data['cross_sell_categories'] : false,
              'filter_sub_category' => true
            );

            $data['ajax_all_products'] = $this->{'model_extension_module_'.self::$_module_name}->getCrossSellTotalProducts($data_x);
          }

          if ($form_data['check'] == 2) {
            $data_x = array(
              'start'                  => isset($this->request->request['start']) ? (int)$this->request->request['start'] : 0,
              'end'                    => isset($this->request->request['end']) ? (int)$this->request->request['end'] : 3,
              'filter_manufacturer_id' => (!empty($form_data['cross_sell_manufacturers']) && $form_data['check'] == 2) ? $form_data['cross_sell_manufacturers'] : false
            );

            $data['ajax_all_products'] = $this->{'model_extension_module_'.self::$_module_name}->getCrossSellTotalProducts($data_x);
          }

          $ajax_products = $this->{'model_extension_module_'.self::$_module_name}->getCrossSellProducts($data_x);

          foreach ($ajax_products as $product) {
            $image = ($product['image']) ? $this->model_tool_image->resize($product['image'], $form_data['sub_images_width'], $form_data['sub_images_height']) : $this->model_tool_image->resize("no_image.jpg", $form_data['sub_images_width'], $form_data['sub_images_height']);
            $price = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
            $special = ((float)$product['special']) ? $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;

            $data['cross_sell_products'][] = array(
              'product_id' => $product['product_id'],
              'thumb'      => $image,
              'name'       => $product['name'],
              'price'      => $price,
              'special'    => $special,
              'href'       => $this->url->link('product/product', 'product_id='.$product['product_id'], true)
            );
          }
        }
      }
    }

    $data['customer_status'] = $this->customer->isLogged() ? 1 : 0;
    $data['coupon'] = $this->load->controller('extension/module/'.self::$_module_name.'/coupon_index');
    $data['voucher'] = $this->load->controller('extension/module/'.self::$_module_name.'/voucher_index');
    $data['reward'] = $this->load->controller('extension/module/'.self::$_module_name.'/reward_index');
    $data['shipping'] = $this->load->controller('extension/module/'.self::$_module_name.'/shipping_index');

    $view = $this->load->view('extension/module/'.self::$_module_name.'/'.self::$_module_name.'_index', $data);

    $this->response->setOutput($view);
  }

  public function coupon_index() {
    $data = array();

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));

    $data['coupon'] = (isset($this->session->data['coupon'])) ? $this->session->data['coupon'] : '';

    return $this->load->view('extension/module/'.self::$_module_name.'/'.self::$_module_name.'_coupon', $data);
   
  }

  public function coupon() {
    $json = array();

    $this->load->language('extension/module/'.self::$_module_name);
    $this->load->model('extension/total/coupon');

    $coupon = (isset($this->request->post['smca_coupon'])) ? $this->request->post['smca_coupon'] : '';

    $coupon_info = $this->{'model_extension_total_coupon'}->getCoupon($coupon);

    if (empty($this->request->post['smca_coupon'])) {
      $json['error'] = $this->language->get('error_smca_coupon_empty');

      unset($this->session->data['coupon']);
    } elseif ($coupon_info) {
      $this->session->data['coupon'] = $this->request->post['smca_coupon'];
      $json['success'] = $this->language->get('text_success_coupon');
    } else {
      $json['error'] = $this->language->get('error_smca_coupon');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function voucher_index() {
    $data = array();

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));

    $data['voucher'] = (isset($this->session->data['voucher'])) ? $this->session->data['voucher'] : '';

    return $this->load->view('extension/module/'.self::$_module_name.'/'.self::$_module_name.'_voucher', $data);

  }

  public function voucher() {
    $json = array();

    $this->load->language('extension/module/'.self::$_module_name);
    $this->load->model('extension/total/voucher');

    $voucher = (isset ($this->request->post['smca_voucher'])) ? $this->request->post['smca_voucher'] : '';

    $voucher_info = $this->{'model_extension_total_voucher'}->getVoucher($voucher);

    if (empty($this->request->post['smca_voucher'])) {
      $json['error'] = $this->language->get('error_smca_voucher_empty');
    } elseif ($voucher_info) {
      $this->session->data['voucher'] = $this->request->post['smca_voucher'];
      $json['success'] = $this->language->get('text_success_voucher');
    } else {
      $json['error'] = $this->language->get('error_smca_voucher');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function reward_index() {
    $data = array();

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));

    $points = $this->customer->getRewardPoints();

    $points_total = 0;

    foreach ($this->cart->getProducts() as $product) {
      if ($product['points']) {
        $points_total += $product['points'];
      }
    }

    if ($points && $points_total) {
      $data['text_loading'] = $this->language->get('text_loading');
      $data['entry_reward'] = sprintf($this->language->get('entry_reward'), $points_total);
      $data['reward'] = isset($this->session->data['reward']) ? $this->session->data['reward'] : '';

       return $this->load->view('extension/module/'.self::$_module_name.'/'.self::$_module_name.'_reward', $data);

    }
  }

  public function reward() {
    $json = array();

    $this->load->language('extension/module/'.self::$_module_name);

    $points = $this->customer->getRewardPoints();

    $points_total = 0;

    foreach ($this->cart->getProducts() as $product) {
      if ($product['points']) {
        $points_total += $product['points'];
      }
    }

    if (empty($this->request->post['smca_reward'])) {
      $json['error'] = $this->language->get('error_smca_reward');
    }

    if ($this->request->post['smca_reward'] > $points) {
      $json['error'] = sprintf($this->language->get('error_smca_points'), $this->request->post['smca_reward']);
    }

    if ($this->request->post['smca_reward'] > $points_total) {
      $json['error'] = sprintf($this->language->get('error_smca_maximum'), $points_total);
    }

    if (!$json) {
      $this->session->data['reward'] = abs($this->request->post['smca_reward']);
      $json['success'] = $this->language->get('text_success_reward');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function shipping_index() {
    $data = array();

    $data = array_merge($data, $this->load->language('extension/module/'.self::$_module_name));

    if ($this->config->get('shipping_status') && $this->config->get('shipping_estimator') && $this->cart->hasShipping()) {

      $data['country_id'] = (isset($this->session->data['shipping_address']['country_id'])) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');

      $this->load->model('localisation/country');

      $data['countries'] = $this->model_localisation_country->getCountries();
      $data['zone_id'] = (isset($this->session->data['shipping_address']['zone_id'])) ? $this->session->data['shipping_address']['zone_id'] : '';
      $data['postcode'] = (isset($this->session->data['shipping_address']['postcode'])) ? $this->session->data['shipping_address']['postcode'] : '';
      $data['shipping_method'] = (isset($this->session->data['shipping_method'])) ? $this->session->data['shipping_method']['code'] : '';

      return $this->load->view('extension/module/'.self::$_module_name.'/'.self::$_module_name.'_shipping', $data);

    }
  }

  public function shipping_quote() {
    $json = array();

    $this->load->language('extension/module/'.self::$_module_name);

    if (!$this->cart->hasProducts()) {
      $json['error']['warning'] = $this->language->get('error_product');
    }

    if (!$this->cart->hasShipping()) {
      $json['error']['warning'] = sprintf($this->language->get('error_smca_no_shipping'), $this->url->link('information/contact', '', true));
    }

    if ($this->request->post['smca_country_id'] == '') {
      $json['error']['country'] = $this->language->get('error_smca_country');
    }

    if (!isset($this->request->post['smca_zone_id']) || $this->request->post['smca_zone_id'] == '') {
      $json['error']['zone'] = $this->language->get('error_smca_zone');
    }

    $this->load->model('localisation/country');

    $country_info = $this->model_localisation_country->getCountry($this->request->post['smca_country_id']);

    if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['smca_postcode'])) < 2 || utf8_strlen(trim($this->request->post['smca_postcode'])) > 10)) {
      $json['error']['postcode'] = $this->language->get('error_smca_postcode');
    }

    if (!$json) {
      $this->tax->setShippingAddress($this->request->post['smca_country_id'], $this->request->post['smca_zone_id']);

      if ($country_info) {
        $country = $country_info['name'];
        $iso_code_2 = $country_info['iso_code_2'];
        $iso_code_3 = $country_info['iso_code_3'];
        $address_format = $country_info['address_format'];
      } else {
        $country = '';
        $iso_code_2 = '';
        $iso_code_3 = '';
        $address_format = '';
      }

      $this->load->model('localisation/zone');

      $zone_info = $this->model_localisation_zone->getZone($this->request->post['smca_zone_id']);

      if ($zone_info) {
        $zone = $zone_info['name'];
        $zone_code = $zone_info['code'];
      } else {
        $zone = '';
        $zone_code = '';
      }

      $this->session->data['shipping_address'] = array(
        'firstname'      => '',
        'lastname'       => '',
        'company'        => '',
        'address_1'      => '',
        'address_2'      => '',
        'postcode'       => $this->request->post['smca_postcode'],
        'city'           => '',
        'zone_id'        => $this->request->post['smca_zone_id'],
        'zone'           => $zone,
        'zone_code'      => $zone_code,
        'country_id'     => $this->request->post['smca_country_id'],
        'country'        => $country,
        'iso_code_2'     => $iso_code_2,
        'iso_code_3'     => $iso_code_3,
        'address_format' => $address_format
      );

      $quote_data = array();

      $this->load->model('setting/extension');

      $results = $this->model_setting_extension->getExtensions('shipping');

      foreach ($results as $result) {
        if ($this->config->get('shipping_'.$result['code'].'_status')) {
          $this->load->model('extension/shipping/'.$result['code']);

          $quote = $this->{'model_extension_shipping_'.$result['code']}->getQuote($this->session->data['shipping_address']);

          if ($quote) {
            $quote_data[$result['code']] = array(
              'title'      => $quote['title'],
              'quote'      => $quote['quote'],
              'sort_order' => $quote['sort_order'],
              'error'      => $quote['error']
            );
          }
        }
      }

      $sort_order = array();

      foreach ($quote_data as $key => $value) {
        $sort_order[$key] = $value['sort_order'];
      }

      array_multisort($sort_order, SORT_ASC, $quote_data);

      $this->session->data['shipping_methods'] = $quote_data;

      if ($this->session->data['shipping_methods']) {
        $json['shipping_method'] = $this->session->data['shipping_methods'];
      } else {
        $json['error']['warning'] = sprintf($this->language->get('error_smca_no_shipping'), $this->url->link('information/contact', '', true));
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function shipping() {
    $json = array();

    $this->load->language('extension/module/'.self::$_module_name);

    if (!empty($this->request->post['smca_shipping_method'])) {
      $shipping = explode('.', $this->request->post['smca_shipping_method']);

      if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
        $json['error'] = $this->language->get('error_smca_shipping');
      }
    } else {
      $json['error'] = $this->language->get('error_smca_shipping');
    }

    if (!$json) {
      $shipping = explode('.', $this->request->post['smca_shipping_method']);
      $json['success'] = $this->language->get('text_success_shipping');
      $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function cartProducts() {
    $json = array();

    $products = $this->cart->getProducts();

    $form_data = (array)$this->config->get(self::$_module_name.'_form_data');
    $text_data = (array)$this->config->get(self::$_module_name.'_text_data');

    $language_code = $this->session->data['language'];

    if (isset($text_data[$language_code])) {
      $json['text_in_cart'] = $text_data[$language_code]['call_button'];
      $json['text_in_cart_vs_options'] = $text_data[$language_code]['call_button_vs_options'];
    }

    if (empty($products)) {
      $json['error'] = html_entity_decode($text_data[$language_code]['empty_text'], ENT_QUOTES, 'UTF-8');
    }

    if (!isset($json['error'])) {
      $json['cart_products'] = array();
      $json['cart_products_vs_options'] = array();

      $this->load->model('catalog/product');

      foreach ($products as $product) {
        $options = $this->model_catalog_product->getProductOptions($product['product_id']);

        if (!$options) {
          $json['cart_products'][] = $product['product_id'];
        } else {
          $json['cart_products_vs_options'][] = $product['product_id'];
        }
      }
    }

    $json['add_function_selector'] = $form_data['add_function_selector'];
    $json['add_id_selector'] = $form_data['add_id_selector'];

    $this->load->language('checkout/cart');

    // totals
    $this->load->model('setting/extension');

    $total_data = array();
    $total = 0;
    $taxes = $this->cart->getTaxes();

    // display prices
    if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
      $sort_order = array();

      $results = $this->model_setting_extension->getExtensions('total');

      foreach ($results as $key => $value) {
        $sort_order[$key] = $this->config->get('total_' . $value['code'].'_sort_order');
      }

      array_multisort($sort_order, SORT_ASC, $results);

      foreach ($results as $result) {
        if ($this->config->get('total_'.$result['code'].'_status')) {
          $this->load->model('extension/total/'.$result['code']);

          $this->{'model_extension_total_'.$result['code']}->getTotal(array('totals' => &$total_data, 'total' => &$total, 'taxes' => &$taxes));
        }
      }

      $sort_order = array();

      foreach ($total_data as $key => $value) {
        $sort_order[$key] = $value['sort_order'];
      }

      array_multisort($sort_order, SORT_ASC, $total_data);
    }

    $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function saveCart() {
    $json = array();

    // connect models array
    $models = array('extension/module/'.self::$_module_name, 'setting/extension', 'tool/image', 'tool/upload');
    foreach ($models as $model) {
      $this->load->model($model);
    }

    $this->load->language('extension/module/'.self::$_module_name);

    $form_data = (array)$this->config->get(self::$_module_name.'_form_data');

    if (isset($this->request->request['type'])) {

      if ($this->request->request['type'] == 'wishlist') {

        $this->load->language('account/wishlist');

        if (!isset($this->session->data['wishlist'])) {
          $this->session->data['wishlist'] = array();
        }

        foreach ($this->cart->getProducts() as $product) {
          if (!in_array($product['product_id'], $this->session->data['wishlist'])) {
            $this->session->data['wishlist'][] = $product['product_id'];
          }
        }

        $visitor_id   = $this->customer->isLogged() ? $this->customer->getEmail() : $this->session->getId();
        $visitor_data = $this->{'model_extension_module_'.self::$_module_name}->getSavedCart($visitor_id);

        if (!$visitor_data) {
          $data_x = array(
            'cart'    => serialize($this->cart->getProducts()),
            'totals'  => $this->cart->getTotal(),
            'ip'      => $this->request->server['REMOTE_ADDR'],
            'visitor' => $visitor_id,
            'email'   => $this->request->request['save_cart_email']
          );
          $this->{'model_extension_module_'.self::$_module_name}->addSavedCart($data_x);
        } else {
          $data_x = array(
            'cart'    => serialize($this->cart->getProducts()),
            'totals'  => $this->cart->getTotal(),
            'visitor' => $visitor_id,
            'email'   => $this->request->request['save_cart_email']
          );
          $this->{'model_extension_module_'.self::$_module_name}->updateSavedCart($data_x);
        }

        $json['success'] = $this->language->get('text_success_send');
        $json['total']   = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
      }

      if ($this->request->request['type'] == 'email') {

        if (isset($this->request->request['save_cart_email']) && !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $this->request->request['save_cart_email'])) {
          $json['error'] = $this->language->get('error_email_send');
        }

        if (!isset($json['error'])) {
          foreach ($this->cart->getProducts() as $product) {
            $product_total = 0;

            foreach ($this->cart->getProducts() as $product_2) {
              if ($product_2['product_id'] == $product['product_id']) {
                $product_total += $product_2['quantity'];
              }
            }

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

            // display price
            $price = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;

            // display tax
            if ($this->config->get('config_tax')) {
              $tax = $this->currency->format((float)$product['price'], $this->session->data['currency']);
              $tax_total = $this->currency->format(($product['price'] * $product['quantity']), $this->session->data['currency']);
            } else {
              $tax = false;
              $tax_total = false;
            }

            // display unit total
            $total = (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) ? $this->currency->format(($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity']), $this->session->data['currency']) : false;

            $image = $product['image'] ? $this->model_tool_image->resize($product['image'], 50, 50): $this->model_tool_image->resize("no_image.png", 50, 50);

            $data['products'][] = array(
              'image'     => $image,
              'name'      => $product['name'],
              'model'     => $product['model'],
              'option'    => $option_data,
              'quantity'  => $product['quantity'],
              'price'     => $price,
              'total'     => $total,
              'href'      => $this->url->link('product/product', 'product_id='.$product['product_id'], true)
            );
          }

          $data['time']            = date('m/d/Y h:i:s a', time());
          $data['text_image']      = $this->language->get('column_image');
          $data['text_product']    = $this->language->get('column_name');
          $data['text_model']      = $this->language->get('column_model');
          $data['text_quantity']   = $this->language->get('column_quantity');
          $data['text_price']      = $this->language->get('column_price');
          $data['text_total']      = $this->language->get('column_total');
          $data['text_cart_saved'] = sprintf($this->language->get('text_cart_saved'), $data['time']);
          $data['logo']            = $this->config->get('config_url').'image/'.$this->config->get('config_logo');
          $data['store_name']      = $this->config->get('config_name');
          $data['store_url']       = $this->config->get('config_url');

          $total_data = array();
          $total = 0;
          $taxes = $this->cart->getTaxes();

          if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
              $sort_order[$key] = $this->config->get('total_' . $value['code'].'_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
              if ($this->config->get('total_'.$result['code'].'_status')) {
                $this->load->model('extension/total/'.$result['code']);

                $this->{'model_extension_total_'.$result['code']}->getTotal(array('totals' => &$total_data, 'total' => &$total, 'taxes' => &$taxes));
              }

              $sort_order = array();

              foreach ($total_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
              }

              array_multisort($sort_order, SORT_ASC, $total_data);
            }
          }

          // order totals
          foreach ($total_data as $total) {
            $data['totals'][] = array(
              'title' => $total['title'],
              'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
            );
          }

         
          $html = $this->load->view('mail/'.self::$_module_name.'_mail', $data);
          
          // email notification
          $op_version = explode(".", VERSION);

          if ($op_version[2] <= 1) {
            $mail = new Mail($this->config->get('config_mail'));
          } else {
            $mail = new Mail();
          }
          $mail->protocol = $this->config->get('config_mail_protocol');
          $mail->parameter = $this->config->get('config_mail_parameter');
          $mail->smtp_hostname = $this->config->get('config_mail_smtp_host');
          $mail->smtp_username = $this->config->get('config_mail_smtp_username');
          $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
          $mail->smtp_port = $this->config->get('config_mail_smtp_port');
          $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
          $mail->setFrom($this->config->get('config_email'));
          $mail->setSender($this->config->get('config_name'));
          $mail->setSubject($this->language->get('text_saved_cart').$data['time']);
          $mail->setHtml($html);

          if ($form_data['new_saved_cart_admin_alert']) {
            $emails = explode(',', $form_data['email_for_notification']);
            $emails[] = $this->request->request['save_cart_email'];

            foreach ($emails as $email) {
              if ($email && preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $email)) {
                $mail->setTo($email);
                $mail->send();
              }
            }
          } else {
            $mail->setTo($this->request->request['save_cart_email']);
            $mail->send();
          }

          $visitor_id   = $this->customer->isLogged() ? $this->customer->getEmail() : $this->session->getId();
          $visitor_data = $this->{'model_extension_module_'.self::$_module_name}->getSavedCart($visitor_id);

          if (!$visitor_data) {
            $data_x = array(
              'cart'    => serialize($this->cart->getProducts()),
              'totals'  => $this->cart->getTotal(),
              'ip'      => $this->request->server['REMOTE_ADDR'],
              'visitor' => $visitor_id,
              'email'   => $this->request->request['save_cart_email']
            );
            $this->{'model_extension_module_'.self::$_module_name}->addSavedCart($data_x);
          } else {
            $data_x = array(
              'cart'    => serialize($this->cart->getProducts()),
              'totals'  => $this->cart->getTotal(),
              'visitor' => $visitor_id,
              'email'   => $this->request->request['save_cart_email']
            );
            $this->{'model_extension_module_'.self::$_module_name}->updateSavedCart($data_x);
          }

          $json['success'] = $this->language->get('text_success_send');
        }
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  // to be continued...
}
?>
