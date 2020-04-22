<?php
class ControllerExtensionModuleExample extends Controller {

  public function sendRequest($url, $type = 'get', $params = [], $json = false)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($type == 'post' || $type == 'put') {
        curl_setopt($ch, CURLOPT_POST, true);

        if ($json) {
            $params = json_encode($params);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($params)
            ]);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Poster (http://joinposter.com)');

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
  }


  public function index() {
    // Загружаем "модель"
    $this->load->model('extension/module/example');
    $data = array();
    // Загружаем настройки (для проверки включен модуль или нет)
    $data['module_example_status'] = $this->model_extension_module_example->LoadSettings();  


    /*$path = storage_path('logs/laravel*.log'); 
    $fileError=[];  
    foreach(glob($path) as $file) { 
    // далее получаем последний добавленный/измененный файл      
    $fileError[] = $file; // массив всех файлов       
    }
    return $fileError;    
*/
    $log = New Log('example.txt');
    $log -> write('Начало');
    

    //получаем данные продукта по id
    /*$getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>222]);
    if ($getProduct) {
        $this->log->write('Есть продукт');
    }
    else
    {
        $this->log->write('Нет такого продукта'); 
    }
    $this->log->write($getProduct);
    return;*/


    // Загружаем языковой файл
    $data += $this->load->language('extension/module/example');
    // Хлебные крошки
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );
    $data['breadcrumbs'][] = array(
      'text' => $data['heading_title'],
      'href' => $this->url->link('extension/module/example')
    );

    // Загружаем остальное
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');


    //Получение Товаров в Интернет магазине
    $product = $this->load->model('catalog/product');
    $totalProducts = $this->model_catalog_product->getTotalProducts(); //кол-во продуктов
    $products_im = $this->model_catalog_product->getProducts(); //массив продуктов
    $data['totalProductsIM']=$totalProducts;
    $log->write($totalProducts);
    $log->write(count($products_im));
    // foreach ($products as $key => $value) {
    //     $log->write([$value['product_id'],$value['name']]);
    // }

    //ПРИМЕР получение тех карт
    $url = 'https://joinposter.com/api/menu.getProducts'
     . '?token='.JOINPOSTER_TOKEN;
    $sendRequest = $this->sendRequest($url);
    $data_decode = json_decode($sendRequest);     
    //$log->write($data_decode);
    $data['dataRequest'] = json_encode($data_decode->response);

    //кол-во заисанных товаров
    $count_write_poduct_db=0;
    $data['count_write_poduct_db']=$count_write_poduct_db;
    foreach ($data_decode->response as $k_poster_product => $v_poster_product) {
        $compare=false;
        if ($v_poster_product->spots[0]->visible == 1) {            
            $data['posterProducts'][$k_poster_product]['product_id']= $v_poster_product->product_id;
            $data['posterProducts'][$k_poster_product]['product_name']=$v_poster_product->product_name;
            $data['posterProducts'][$k_poster_product]['category_name']=$v_poster_product->category_name;
            $data['posterProducts'][$k_poster_product]['hidden']=$v_poster_product->hidden;
            $data['posterProducts'][$k_poster_product]['visible']=$v_poster_product->spots[0]->visible;
            $data['posterProducts'][$k_poster_product]['menu_category_id']=$v_poster_product->menu_category_id;

            foreach ($products_im as $k_products_im => $v_products_im) {
                if ($v_products_im['name']==$data['posterProducts'][$k_poster_product]['product_name']){
                    $data['posterAndImProducts'][$v_products_im['product_id']]=['name'=>$v_products_im['name'],'im_product_id'=>$v_products_im['product_id'],'poster_product_id'=>$data['posterProducts'][$k_poster_product]['product_id']];
                    //записываем в базу данных
                    $getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>$v_products_im['product_id']]);
                    if (!$getProduct) {
                        $this->model_extension_module_example->addProducts(array('name_im' => $v_products_im['name'],'name_poster' => $data['posterProducts'][$k_poster_product]['product_name'],'id_product_im' => $v_products_im['product_id'],'id_product_poster' => $data['posterProducts'][$k_poster_product]['product_id'],'cost_im' => 0,'cost_poster' => 0));                            
                            $data['count_write_poduct_db']=++$count_write_poduct_db;
                    }
                    //для сопоставления
                    $compare = True;
                }
                
            }
            if (!$compare) {
                $data['notPosterAndImProducts'][]=['name'=>$data['posterProducts'][$k_poster_product]['product_name'],'poster_product_id'=>$data['posterProducts'][$k_poster_product]['product_id']];
            }
        }        
    }
    $data['countPosterAndImProducts']=count($data['posterAndImProducts']);
    // $log->write($data['posterAndImProducts']);
    // $log->write($data['posterAndImProducts']['im_product_id'][43]['poster_product_id']);
    // print_r($data_decode->response[5]->spots);


    //получение данных из заказа
    $Order = $this->load->model('checkout/order');
    $getOrder = $this->model_checkout_order->getOrder(16);
    $getOrderProducts = $this->model_checkout_order->getOrderProducts(16);
    //заказ
    $log->write($getOrder);
    $data['getOrder'] = ['order_id'=>$getOrder['order_id'],'firstname'=>$getOrder['firstname'],'telephone'=> $getOrder['telephone'],'invoice_prefix'=>$getOrder['invoice_prefix'],'payment_address_1'=>$getOrder['payment_address_1'],'payment_address_2'=>$getOrder['payment_address_2'],'payment_method'=>$getOrder['payment_method'],'shipping_method'=>$getOrder['shipping_method'],'total'=>$getOrder['total'],'comment'=>$getOrder['comment']];
    $log->write($data['getOrder']);

    //print_r($data['getOrder']);
    // print_r([$getOrder['firstname'],$getOrder['telephone'],$getOrder['invoice_prefix'],$getOrder['payment_address_1'],$getOrder['payment_address_2'],$getOrder['payment_method'],$getOrder['shipping_method'],$getOrder['total']]);
    //продукты в заказе
    foreach ($getOrderProducts as $key => $value) {
      $getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>$value['product_id']]);
      if (!$getProduct) {
          $data['error']='Не нашли в базе ИМ продукт с id '.$value['product_id'].' #заказа:'.$getOrder['order_id'];
          $log->write($data['error']);
      }
      $data['getOrderProducts'][]=['product_id'=>$getProduct['id_product_im'],'name'=>$getProduct['name_poster'],'price'=>$value['price'],'quantity'=>$value['quantity'],'total'=>$value['total']];
      $products_for_order[$key] = ['product_id'=>$getProduct['id_product_poster'],'count'=>$value['quantity']];               
      //print_r([$value['product_id'],$value['name'],$value['price'],$value['quantity'],$value['total'],$value['quantity']]);
    }      

    //для красивого отображения в чеке
    if ($getOrder['shipping_method']=='Самовывоз из заведения') {        
        $getOrder['shipping_method']='Самовывоз';
    }
    elseif($getOrder['shipping_method']=='Доставка от суммы заказа до 1000 руб.'){
            $getOrder['shipping_method']=''; 
            //добавляю доставку платную
            $products_for_order[] = ['product_id'=>93,'count'=>1];   
    }
    elseif($getOrder['shipping_method']=='Бесплатная доставка'){
            $getOrder['shipping_method']=''; 
            $products_for_order[] = ['product_id'=>94,'count'=>1];
    }

    if ($getOrder['payment_method']=='Оплата наличными (в комментариях напишите с какой суммы подготовить сдачу)') {
        $getOrder['payment_method']='Наличные';
    }
    elseif($getOrder['payment_method']=='Оплата по банковскому терминалу'){
            $getOrder['payment_method']='Безнал';
    }

    $log->write($products_for_order);
    

    if (!empty($data['error'])) {
        $log->write('Есть ошибка - не могу выполнить запрос:'.$data['error']);
    }
    else{
      //ПРИМЕР создать online заказ 
      $url = 'https://joinposter.com/api/incomingOrders.createIncomingOrder'
      .'?token='.JOINPOSTER_TOKEN;
      $incoming_order = [
          'spot_id'   => 1,
          'first_name' => $getOrder['firstname'],
          'phone'     =>  $getOrder['telephone'],
          'address' => $getOrder['payment_address_1'].' '.$getOrder['payment_address_2'],
          'comment' => $getOrder['order_id'].' '.$getOrder['shipping_method'].' '.$getOrder['payment_method'].$getOrder['comment'],
          'products'  => $products_for_order,
      ];
      $log->write($incoming_order);
      /*    
      $data_incoming_order = $this->sendRequest($url, 'post', $incoming_order); 
      $log->write($data_incoming_order); 
      $data_decode = json_decode($data_incoming_order);
      // $dataRequest = json_encode($data_decode->response);   
      if (isset($data_decode->error)) {
          $log->write($data_decode->message); 
      }
      elseif (isset($data_decode->response)) {
          $log->write($data_decode->response->incoming_order_id);
      }*/

    }
    
    // $log->write($getOrder);  
    // $log->write($getOrderProducts); 


    

    $this->response->setOutput($this->load->view('extension/module/example', $data));
    
  }
}
?>