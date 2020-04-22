<?php
class ControllerCheckoutSuccess extends Controller {
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
			$this->load->language('checkout/success');			
			$log_poster = New Log('log_poster.txt');
	    $log_poster -> write('Запуск Success');
	    // return;

		  if (isset($this->session->data['order_id'])) {
				
		  	//загружаю модели
        $this->load->model('extension/module/example');
        $product = $this->load->model('catalog/product');
            // каталог продукции			
		    $totalProducts = $this->model_catalog_product->getTotalProducts(); //кол-во продуктов
		    $products_im = $this->model_catalog_product->getProducts(); //массив продуктов
		    $data['totalProductsIM']=$totalProducts;

			//получение данных из заказа
		    $Order = $this->load->model('checkout/order');
		    $getOrder = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		    $getOrderProducts = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);

		    //заказ
		    $data['getOrder'] = ['order_id'=>$getOrder['order_id'],'firstname'=>$getOrder['firstname'],'telephone'=> $getOrder['telephone'],'invoice_prefix'=>$getOrder['invoice_prefix'],'payment_address_1'=>$getOrder['payment_address_1'],'payment_address_2'=>$getOrder['payment_address_2'],'payment_method'=>$getOrder['payment_method'],'shipping_method'=>$getOrder['shipping_method'],'total'=>$getOrder['total']];

		    //продукты в заказе
		    foreach ($getOrderProducts as $key => $value) {
                $getProduct=Null;
		        $getProduct = $this->model_extension_module_example->getProduct(['id_product_im'=>$value['product_id']]);
		        if (!$getProduct) {
		            $data['error']='Не нашли в базе ИМ продукт с id '.$value['product_id'].' #заказа:'.$getOrder['order_id'];
		            $log_poster -> write($data['error']);		            
		        }
                else{
                    $data['getOrderProducts'][]=['product_id'=>$getProduct['id_product_im'],'name'=>$getProduct['name_poster'],'price'=>$value['price'],'quantity'=>$value['quantity'],'total'=>$value['total']];
                    $products_for_order[$key] = ['product_id'=>$getProduct['id_product_poster'],'count'=>$value['quantity']];
                }
		        
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
			      				//доставка бесплатная
			              $getOrder['shipping_method']=''; 
			              $products_for_order[] = ['product_id'=>94,'count'=>1];
			      }
			      elseif($getOrder['shipping_method']=='Доставка от суммы заказа до 1000 руб.'){
			      				//доставка бесплатная
			              $getOrder['shipping_method']=''; 
			              $products_for_order[] = ['product_id'=>94,'count'=>1];
			      }
			      
			      if ($getOrder['payment_method']=='Оплата наличными (в комментариях напишите с какой суммы подготовить сдачу)') {
			          $getOrder['payment_method']='Наличные';
			      }
			      elseif($getOrder['payment_method']=='Оплата по банковскому терминалу'){
			              $getOrder['payment_method']='Безнал';
			      }


            if (!empty($data['error'])) {
                $log_poster->write('Есть ошибка - не могу выполнить запрос:'.$data['error']);
            }
            else{ //ошибок не было создаём заказ
                //ПРИМЕР создать online заказ 
                $url = 'https://joinposter.com/api/incomingOrders.createIncomingOrder'
                .'?token=908754:3868312c50cd67bc0e4d82b3bb9eb37d';
                $incoming_order = [
                    'spot_id'   => 1,
                    'first_name' => $getOrder['firstname'],
                    'phone'     =>  $getOrder['telephone'],
                    'address' => '#'.$getOrder['order_id'].' '.$getOrder['payment_address_1'].' '.$getOrder['payment_address_2'],
                    'comment' => $getOrder['shipping_method'].' '.$getOrder['payment_method'].' тел.'.$getOrder['telephone'],
                    'products'  => $products_for_order,
                ];
                $log_poster->write($incoming_order);
                
                //добавляю новый статус продукт
                $data_incoming_order = $this->sendRequest($url, 'post', $incoming_order); 
                $log_poster->write($data_incoming_order); 
                $data_decode = json_decode($data_incoming_order);
     
                if (isset($data_decode->error)) {
                    //пришла ошибка
                    $log_poster->write($data_decode->message); 
                }
                elseif (isset($data_decode->response)) {
                    $log_poster->write($data_decode->response->incoming_order_id);

                    $this->load->model('extension/module/checkstatusproduct');

                    $addStatusPoduct = $this->model_extension_module_checkstatusproduct->addStatusProduct(['im_order_id'=>$getOrder['order_id'],'poster_order_id'=>$data_decode->response->incoming_order_id,'poster_transaction_id'=>0,'poster_status_order'=>'not_ready','status_sms'=>'not_send']);
                }
            }		

			//очистка корзины
			$this->cart->clear();
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		  }

		  $this->document->setTitle($this->language->get('heading_title'));

		  $data['breadcrumbs'] = array();

		  $data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
		  );

		  $data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_basket'),
				'href' => $this->url->link('checkout/cart')
		  );

		  $data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_checkout'),
				'href' => $this->url->link('checkout/checkout', '', true)
		  );

		  $data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_success'),
				'href' => $this->url->link('checkout/success')
		  );

		  if ($this->customer->isLogged()) {
				$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		  } else {
                $data['text_poster_message'] = sprintf('Ваш заказ принят в работу и начнёт готовится');
				$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		  }

		  $data['continue'] = $this->url->link('common/home');
		  $data['column_left'] = $this->load->controller('common/column_left');
		  $data['column_right'] = $this->load->controller('common/column_right');
		  $data['content_top'] = $this->load->controller('common/content_top');
		  $data['content_bottom'] = $this->load->controller('common/content_bottom');
		  $data['footer'] = $this->load->controller('common/footer');
		  $data['header'] = $this->load->controller('common/header');

		  $this->response->setOutput($this->load->view('common/success', $data));
	 }
}