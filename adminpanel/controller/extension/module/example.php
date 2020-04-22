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

    // Загружаем "модель" модуля
    $this->load->model('extension/module/example');

    // Сохранение настроек модуля, когда пользователь нажал "Записать"
    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
      // Вызываем метод "модели" для сохранения настроек
      $this->model_extension_module_example->SaveSettings();
      // Выходим из настроек с выводом сообщения
      $this->session->data['success'] = 'Настройки сохранены';
      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
    }

    // Загружаем настройки через метод "модели"
    $data = array();
    $data['module_example_status'] = $this->model_extension_module_example->LoadSettings();
    // Загружаем языковой файл
    $data += $this->load->language('extension/module/example');
    // Загружаем "хлебные крошки"
    $data += $this->GetBreadCrumbs();

    // Кнопки действий
    $data['action'] = $this->url->link('extension/module/example', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
    // Загрузка шаблонов для шапки, колонки слева и футера
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
    $data['test']="test text";

    //МОЙ СКРИПТ
    //определяю свои данные
    $log = New Log('exampleadmin.txt');
    $log -> write('Начало');


    //получаю онлайн чек
    // $url = 'https://joinposter.com/api/incomingOrders.getIncomingOrder'
    //  . '?token='.JOINPOSTER_TOKEN
    //  . '&incoming_order_id=28';

    // $sendRequest = $this->sendRequest($url);
    // $data_decode = json_decode($sendRequest);  
    // $online_check = $data_decode->response;
    // $log -> write('получил online чек по id');
    // $log -> write($online_check);
    // if ($online_check->status==1){
    //   $log -> write('online чек принят');
    //   $transaction_id = $online_check->transaction_id;
    //   //получаю чек по транзакции

    //   $url = 'https://joinposter.com/api/dash.getTransaction'
    //  . '?token='.JOINPOSTER_TOKEN
    //  . '&transaction_id='.$transaction_id
    //  . '&include_history=false'
    //  . '&include_products=true';
    //   $sendRequest = $this->sendRequest($url);
    //   $check = json_decode($sendRequest);      
    //   $check = $check->response[0];
    //   $log -> write('получил чек по транзакции (transaction_id)');
    //   $log -> write($check);
    //   $log -> write($check->products);

    //   $status_gotov = false;
    //   foreach ($check->products as $kproducts => $vProduct) {
    //     if ($vProduct->product_id==96) {
    //       $status_gotov = true;
    //       $log -> write('Заказ готов');
    //       //отправляю смс
    //     }  
    //   }
    // }
    // else{
    //   $log -> write('online чек еще не принят');  
    // }
    
    //получить все чеки
    /*$url = 'https://joinposter.com/api/transactions.getTransactions'
     . '?token='.JOINPOSTER_TOKEN
     . '&date_from=2020-04-20'
     . '&date_to=2020-04-20'
     . '&per_page=10'
     . '&page=1';

    $sendRequest = $this->sendRequest($url);
    $data_decode = json_decode($sendRequest);  
    $log -> write($data_decode);*/

    /*$url = 'https://joinposter.com/api/payments.getOpenTransactionsOnTable'
     . '?token='.JOINPOSTER_TOKEN;
    $sendRequest = $this->sendRequest($url);
    $data_decode = json_decode($sendRequest);  
    $log -> write($data_decode);*/
   
    
    //return;







    //Получение Товаров в Интернет магазине
    $product = $this->load->model('catalog/product');
    $totalProducts = $this->model_catalog_product->getTotalProducts(); //кол-во продуктов
    $products_im = $this->model_catalog_product->getProducts(); //массив продуктов
    $data['totalProductsIM']=$totalProducts;
    $log->write($totalProducts);
    $log->write(count($products_im));

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
                if (mb_strtolower($v_products_im['name'])==mb_strtolower($data['posterProducts'][$k_poster_product]['product_name'])){
                    $data['posterAndImProducts'][$v_products_im['product_id']]=['name'=>$v_products_im['name'],'im_product_id'=>$v_products_im['product_id'],'poster_product_id'=>$data['posterProducts'][$k_poster_product]['product_id']];
                    //записываем в базу данных
                    
                    $getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>$v_products_im['product_id']]);
                    
                    /*if($v_products_im['product_id']==74){
                        $log->write($v_products_im['name']);
                        $log->write($data['posterProducts'][$k_poster_product]['product_name']);
                        $log->write($getProduct);
                        
                    }*/
                    if (!$getProduct) {
                        $this->model_extension_module_example->addProducts(array('name_im' => $v_products_im['name'],'name_poster' => $data['posterProducts'][$k_poster_product]['product_name'],'id_product_im' => $v_products_im['product_id'],'id_product_poster' => $data['posterProducts'][$k_poster_product]['product_id'],'cost_im' => 0,'cost_poster' => 0));                            
                            $data['count_write_poduct_db']=++$count_write_poduct_db;
                    }
                    //для сопоставления
                    $compare = True;
                }                
            }
            if (!$compare) {
                $data['notComparePosterProducts'][]=['name'=>$data['posterProducts'][$k_poster_product]['product_name'],'poster_product_id'=>$data['posterProducts'][$k_poster_product]['product_id']];
                
            }
        }        
    }
    $data['countPosterAndImProducts']=count($data['posterAndImProducts']);

    //проверяю какие не подошли из базы данных сопосталвенных???
    foreach ($products_im as $k_products_im => $v_products_im) {
    	$getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>$v_products_im['product_id']]);
    	if (!$getProduct) {   
    		$data['notCompareImProducts'][]=['name'=>$v_products_im['name'],'id_product_im'=>$v_products_im['product_id']];     
      }            
		}


    //получение данных из заказа
    $Order = $this->load->model('checkout/order');
    $getOrder = $this->model_checkout_order->getOrder(55);
    $getOrderProducts = $this->model_checkout_order->getOrderProducts(55);
    //заказ
    //$log->write($getOrder);
    $data['getOrder'] = ['order_id'=>$getOrder['order_id'],'firstname'=>$getOrder['firstname'],'telephone'=> $getOrder['telephone'],'invoice_prefix'=>$getOrder['invoice_prefix'],'payment_address_1'=>$getOrder['payment_address_1'],'payment_address_2'=>$getOrder['payment_address_2'],'payment_method'=>$getOrder['payment_method'],'shipping_method'=>$getOrder['shipping_method'],'total'=>$getOrder['total'],'comment'=>$getOrder['comment']];
    //$log->write($data['getOrder']);

    $products_for_order=array();
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

    if(!empty($products_for_order)){
			$log->write($products_for_order);   
    }     

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
      if(!empty($incoming_order)){
				$log->write($incoming_order);   
    	}
      
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


    // Выводим в браузер шаблон
    $this->response->setOutput($this->load->view('extension/module/example', $data));

  }

  // Хлебные крошки
  private function GetBreadCrumbs() {
    $data = array(); $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_extension'),
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/example', 'user_token=' . $this->session->data['user_token'], true)
    );
    return $data;
  }

}

?>