<?php
class ControllerCheckoutOrder extends Controller {
		
	public function index() {
		$data['debug_mode']=false;
		$this->load->language('checkout/success');	
		$this->log = New Log('order_'.date("d.m.y").'.txt');				
	    $this->log -> write('Запуск Order');

  	
	  
	  
	 
	  
	  

		 if (isset($this->session->data['order_id'])) {				
		  	////загружаю модели
      	//$this->load->model('extension/module/example');
      	//$product = $this->load->model('catalog/product');
      	////каталог продукции			
		    // $totalProducts = $this->model_catalog_product->getTotalProducts(); //кол-во продуктов
		    // $products_im = $this->model_catalog_product->getProducts(); //массив продуктов
		    // $data['totalProductsIM']=$totalProducts;

				//получение данных из заказа
		    // $Order = $this->load->model('checkout/order');
		    // $getOrder = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		    // $getOrderProducts = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);

		    //заказ
		    // $data['getOrder'] = ['order_id'=>$getOrder['order_id'],'firstname'=>$getOrder['firstname'],'telephone'=> $getOrder['telephone'],'invoice_prefix'=>$getOrder['invoice_prefix'],'payment_address_1'=>$getOrder['payment_address_1'],'payment_address_2'=>$getOrder['payment_address_2'],'payment_method'=>$getOrder['payment_method'],'shipping_method'=>$getOrder['shipping_method'],'total'=>$getOrder['total']];

	    	//продукты в заказе
				/*foreach ($getOrderProducts as $key => $value) {
              $getProduct=Null;
	        $getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>$value['product_id']]);
	        if (!$getProduct) {
	            $data['error']='Не нашли в базе ИМ продукт с id '.$value['product_id'].' #заказа:'.$getOrder['order_id'];
	            $log -> write($data['error']);		            
	        }
              else{
                  $data['getOrderProducts'][]=['product_id'=>$getProduct['id_product_im'],'name'=>$getProduct['name_poster'],'price'=>$value['price'],'quantity'=>$value['quantity'],'total'=>$value['total']];
                  $products_for_order[$key] = ['product_id'=>$getProduct['id_product_poster'],'count'=>$value['quantity']];
              }
		        
		    	}*/
			      
			      // //для красивого отображения в чеке
			      // if ($getOrder['shipping_method']=='Самовывоз из заведения') {        
			      //     $getOrder['shipping_method']='Самовывоз';
			      // }
			      // elseif($getOrder['shipping_method']=='Доставка от суммы заказа до 1000 руб.'){
			      //         $getOrder['shipping_method']=''; 
			      //         //добавляю доставку платную
			      //         $products_for_order[] = ['product_id'=>93,'count'=>1];   
			      // }
			      // elseif($getOrder['shipping_method']=='Бесплатная доставка'){
			      // 				//доставка бесплатная
			      //         $getOrder['shipping_method']=''; 
			      //         $products_for_order[] = ['product_id'=>94,'count'=>1];
			      // }
			      // if ($getOrder['payment_method']=='Оплата наличными (в комментариях напишите с какой суммы подготовить сдачу)') {
			      //     $getOrder['payment_method']='Наличные';
			      // }
			      // elseif($getOrder['payment_method']=='Оплата по банковскому терминалу'){
			      //         $getOrder['payment_method']='Безнал';
			      // }


         //    if (!empty($data['error'])) {
         //        $log->write('Есть ошибка - не могу выполнить запрос:'.$data['error']);
         //    }
         //    else{ //ошибок не было создаём заказ
         //        //ПРИМЕР создать online заказ 
         //        $url = 'https://joinposter.com/api/incomingOrders.createIncomingOrder'
         //        .'?token=908754:3868312c50cd67bc0e4d82b3bb9eb37d';
         //        $incoming_order = [
         //            'spot_id'   => 1,
         //            'first_name' => $getOrder['firstname'],
         //            'phone'     =>  $getOrder['telephone'],
         //            'address' => '#'.$getOrder['order_id'].' '.$getOrder['payment_address_1'].' '.$getOrder['payment_address_2'],
         //            'comment' => $getOrder['shipping_method'].' '.$getOrder['payment_method'],
         //            'products'  => $products_for_order,
         //        ];
         //        $log->write($incoming_order);

                
         //        $data_incoming_order = $this->sendRequest($url, 'post', $incoming_order); 
         //        $log->write($data_incoming_order); 
         //        $data_decode = json_decode($data_incoming_order);
     
         //        if (isset($data_decode->error)) {
         //            //пришла ошибка
         //            $log->write($data_decode->message); 
         //        }
         //        elseif (isset($data_decode->response)) {
         //            $log->write($data_decode->response->incoming_order_id);
         //        }
         //    }

            

            			

			//очистка корзины
			// $this->cart->clear();
			// unset($this->session->data['shipping_method']);
			// unset($this->session->data['shipping_methods']);
			// unset($this->session->data['payment_method']);
			// unset($this->session->data['payment_methods']);
			// unset($this->session->data['guest']);
			// unset($this->session->data['comment']);
			// unset($this->session->data['order_id']);
			// unset($this->session->data['coupon']);
			// unset($this->session->data['reward']);
			// unset($this->session->data['voucher']);
			// unset($this->session->data['vouchers']);
			// unset($this->session->data['totals']);
		  }

		  //$this->document->setTitle($this->language->get('heading_title'));

		  if(isset($this->request->get['order'])){
		  	$data['order_id']=$this->request->get['order'];
		  }
		  elseif (isset($this->request->post['order_id'])) {
		  	$data['order_id']=$this->request->post['order_id'];
	  		$this->log -> write('запрос от Ajax');
	  		$this->log -> write($this->request->post);			
	  	}	

		  $data['breadcrumbs'] = array();

		  $data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
		  );

		  // $data['breadcrumbs'][] = array(
				// 'text' => $this->language->get('text_basket'),
				// 'href' => $this->url->link('checkout/cart')
		  // );

		  // $data['breadcrumbs'][] = array(
				// 'text' => $this->language->get('text_checkout'),
				// 'href' => $this->url->link('checkout/checkout', '', true)
		  // );

		  $data['breadcrumbs'][] = array(
				'text' => 'Заказ №'.$data['order_id'],// $this->language->get('text_success')
				'href' => $this->url->link('checkout/success')
		  );

		  // if ($this->customer->isLogged()) {
				// $data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		  // } else {
    //             $data['text_poster_message'] = sprintf('Ваш заказ принят в работу и начнёт готовится');
				// $data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		  // }

		  $data['continue'] = $this->url->link('common/home');
		  $data['column_left'] = $this->load->controller('common/column_left');
		  $data['column_right'] = $this->load->controller('common/column_right');
		  $data['content_top'] = $this->load->controller('common/content_top');
		  $data['content_bottom'] = $this->load->controller('common/content_bottom');
		  $data['footer'] = $this->load->controller('common/footer');
		  $data['header'] = $this->load->controller('common/header');


		  //получение данных из заказа
		  	
		    $Order = $this->load->model('checkout/order');
		    $getOrder = $this->model_checkout_order->getOrder($data['order_id']);
		    $getOrderProducts = $this->model_checkout_order->getOrderProducts($data['order_id']);		    

		    //заказ
		    $data['getOrder'] = ['order_id'=>$getOrder['order_id'],'firstname'=>$getOrder['firstname'],'telephone'=> $getOrder['telephone'],'invoice_prefix'=>$getOrder['invoice_prefix'],'payment_address_1'=>$getOrder['payment_address_1'],'payment_address_2'=>$getOrder['payment_address_2'],'payment_method'=>$getOrder['payment_method'],'shipping_method'=>$getOrder['shipping_method'],'total'=>$getOrder['total']];

		    $this->load->model('extension/module/checkstatusproduct');
		    //$data['getPosterStatusOrder'] = $this->model_extension_module_checkstatusproduct->getPosterStatusOrder(['im_order_id'=>$data['order_id']]);
		    $getPosterStatusOrder = $this->model_extension_module_checkstatusproduct->getPosterStatusOrder(["im_order_id"=>$data['order_id']]);
		    $data['getPosterStatusOrder']=$getPosterStatusOrder[0];	
		  //  $this->log -> write('получил $getPosterStatusOrder[0]');
		  //  $this->log -> write($getPosterStatusOrder[0]);
		  //  return;
		    if (!empty($data['getPosterStatusOrder'])) {
		    	$this->log -> write($data['getPosterStatusOrder']);
		    	if($data['getPosterStatusOrder']['poster_status_order']=="cancell"){
		    		$this->log -> write("Заказ Отменен");
		    		$data['PosterStatusOrderText']="отменен";
		    	}
		    	elseif($data['getPosterStatusOrder']['poster_status_order']=="not_ready"){
		    		$data['PosterStatusOrderText']="в обработке";
		    		$data['PosterStatusOrder']="not_ready";
		    		$this->log -> write("Заказ в обработке");
		    	}
		    	elseif($data['getPosterStatusOrder']['poster_status_order']=="accepted"){
		    		$data['PosterStatusOrderText']="принят в работу, готовим.";
		    		$data['PosterStatusOrder']="accepted";
		    		$this->log -> write("Заказ принят в работу, готовим. примерное время 15 минут");
		    	}
		    	elseif($data['getPosterStatusOrder']['poster_status_order']=="ready"){ 
		    		//статус заказ готов
			      if ($getOrder['shipping_method']=='Самовывоз из заведения') {
			      	$data['PosterStatusOrderText']="ваш заказ готов, можете его забирать по адресу ТК Пионерный";
			      	$data['PosterStatusOrder']="ready_sam";
			        $this->log -> write("готов, можете его забирать по адресу ТК Пионерный");
			      }
			      elseif($getOrder['shipping_method']=='Доставка от суммы заказа до 1000 руб.'){
			      	$data['PosterStatusOrderText']="ваш заказ отправлен курьером. ожидайте доставки";
			      	$data['PosterStatusOrder']="ready_dostavka";
			         $this->log -> write("ваш заказ отправлен курьером");   
			      }
			      elseif($getOrder['shipping_method']=='Бесплатная доставка'){
			      	$data['PosterStatusOrderText']="ваш заказ отправлен курьером. ожидайте доставки";
			      	$data['PosterStatusOrder']="ready_dostavka";
			        $this->log -> write("ваш заказ отправлен курьером");
			      }
		    	}
		    	elseif($data['getPosterStatusOrder']['poster_status_order']=="close"){ //статус заказ готов
			      	$data['PosterStatusOrderText']="заказ выполнен";
			      	$data['PosterStatusOrder']="close";
			        // $this->log -> write("ваш заказ отправлен курьером");			      
		    	} 	
		    }	  

		  $data['update_link']=$this->url->link('checkout/order', "order=".$data['order_id']);
			  //если это запрос от ajax
		 
		  if (isset($this->request->post['order_id'])) {
		  	//$this->ajax();
		  	$data['order_id']=$this->request->post['order_id'];
	  		// $this->log -> write('запрос от Ajax');
	  		// $this->log -> write($this->request->post);
	  		$json = array();
	  		
	  		if(isset($data['PosterStatusOrderText'])){
	  		    $this->log -> write($data['PosterStatusOrderText']);
	  			$json['status'] = $data['PosterStatusOrderText'];		
					
	  		}
	  		else{
	  			$json['status'] = '';
	  		}	
	  		//проверяю статус
		  	$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json)); 

			return;				
	  	}	
	  	else{ // get
	  		// $addStatusPoduct = $this->model_extension_module_checkstatusproduct->addStatusProduct(['im_order_id'=>55,'poster_order_id'=>66,'poster_transaction_id'=>0,'poster_status_order'=>'not_ready','status_sms'=>'not_send']);
	  		//$this->ajax();
	  		$this->response->setOutput($this->load->view('common/order', $data));
	  	}
			  
	}

	 public function ajax(){
	 	$this->log -> write("Функция ajax");
	 	$this->load->model('extension/module/checkstatusproduct');
    $getPosterStatusOrder['not_ready'] = $this->model_extension_module_checkstatusproduct->getPosterStatusOrder(['poster_status_order'=>'not_ready']);
    $getPosterStatusOrder['accepted'] = $this->model_extension_module_checkstatusproduct->getPosterStatusOrder(['poster_status_order'=>'accepted']);
    $getPosterStatusOrder['ready'] = $this->model_extension_module_checkstatusproduct->getPosterStatusOrder(['poster_status_order'=>'ready']);
    // $this->log -> write('получил getPosterStatusOrder');
    // $this->log -> write($getPosterStatusOrder);   
    // if (!$getPosterStatusOrder['not_ready'] && !$getPosterStatusOrder['accepted']) {
    //   $this->log -> write(['пусто getPosterStatusOrder',$getPosterStatusOrder]);
    //   return;
    // } 
    // else{
    //   foreach ($getPosterStatusOrder as $kgetPosterStatusOrder => $vgetPosterStatusOrder) {
    //     if (!empty($vgetPosterStatusOrder)) {
    //       $this->checkStatus(['getPosterStatusOrder'=>$vgetPosterStatusOrder]);
    //     }        
    //   }
    // }

	 }
}