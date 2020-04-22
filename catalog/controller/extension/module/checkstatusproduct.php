<?php
class ControllerExtensionModuleCheckstatusproduct extends Controller {
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
    // $this->load->model('extension/module/checkstatusproduct');
    // $data = array();
    // // Загружаем настройки (для проверки включен модуль или нет)
    // $data['module_checkstatusproduct_status'] = $this->model_extension_module_checkstatusproduct->LoadSettings();
    // // Загружаем языковой файл
    // $data += $this->load->language('extension/module/checkstatusproduct');
    // // Хлебные крошки
    // $data['breadcrumbs'][] = array(
    //   'text' => $this->language->get('text_home'),
    //   'href' => $this->url->link('common/home')
    // );
    // $data['breadcrumbs'][] = array(
    //   'text' => $data['heading_title'],
    //   'href' => $this->url->link('extension/module/checkstatusproduct')
    // );
    // Загружаем остальное
    // $data['column_left'] = $this->load->controller('common/column_left');
    // $data['column_right'] = $this->load->controller('common/column_right');
    // $data['content_top'] = $this->load->controller('common/content_top');
    // $data['content_bottom'] = $this->load->controller('common/content_bottom');
    // $data['footer'] = $this->load->controller('common/footer');
    // $data['header'] = $this->load->controller('common/header');
    // Выводим на экран

    $log = New Log('checkstatusproduct.txt');
    $log -> write('запуск');
    
    $this->load->model('extension/module/checkstatusproduct');
    $getPosterStatusOrder = $this->model_extension_module_checkstatusproduct->getPosterStatusOrder(['poster_status_order'=>'not_ready']);

    // $SMSC_API = $this->load->controller('Extension/Module/SMSC_API',['phones'=>555,'message'=>'dgte']);
    //         $log -> write($SMSC_API);
   
    // $getPosterStatusOrder = $this->model_extension_module_checkstatusproduct->setStatusOrder(['poster_status_order'=>'ready','id'=>$vStatusOrder['id']]);
    // require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'smsc_api.php' );
    // $SMSCAPI = new ControllerExtensionModuleSMSCAPI();
    //$log -> write($SMSCAPI->index(['phones'=>89026262670,'message'=>'test']));

    if (!$getPosterStatusOrder) {
      //$log -> write('пусто',$getPosterStatusOrder);
      return;
    } 

    foreach ($getPosterStatusOrder as $kStatusOrder => $vStatusOrder) {
      //получил все статусы заказов где не открыты чеки
      // $log -> write($vStatusOrder['im_order_id']);
      $url = 'https://joinposter.com/api/incomingOrders.getIncomingOrder'
      . '?token='.JOINPOSTER_TOKEN
      . '&incoming_order_id='.$vStatusOrder['poster_order_id'];
      $sendRequest = $this->sendRequest($url);
      $data_decode = json_decode($sendRequest);  
      $online_check = $data_decode->response;
      if ($online_check->status==1){
        $log -> write('online чек принят '.$online_check->transaction_id);

        $transaction_id = $online_check->transaction_id;
        //записываю транзакцию в базу данных
        $getPosterStatusOrder = $this->model_extension_module_checkstatusproduct->setStatusOrder(['poster_transaction_id'=>$transaction_id,'id'=>$vStatusOrder['id']]);        
        //получаю чек по транзакции
        $url = 'https://joinposter.com/api/dash.getTransaction'
       . '?token='.JOINPOSTER_TOKEN
       . '&transaction_id='.$transaction_id
       . '&include_history=false'
       . '&include_products=true';
        $sendRequest = $this->sendRequest($url);
        $check = json_decode($sendRequest);      
        $check = $check->response[0];
        $status_gotov = false;
        foreach ($check->products as $kproducts => $vProduct) {
          if ($vProduct->product_id==96) {
            $status_gotov = true;
            $log -> write('Заказ готов');    
            $log -> write('Отправляю смс');  

            //отправка СМС
            $Order = $this->load->model('checkout/order');
            $getOrder = $this->model_checkout_order->getOrder($vStatusOrder['im_order_id']);
            $log -> write('Получаю данные заказа из ИМ');  
            // $log -> write($getOrder);
            $message='Ваш заказ готов. КомуЧЁ GRILL';  
            if ($getOrder['shipping_method']=='Самовывоз из заведения') {        
                $message='Ваш заказ готов. Забирайте скорее, горячее вкуснее. КомуЧё GRILL';
            }
            elseif($getOrder['shipping_method']=='Доставка от суммы заказа до 1000 руб.'){
                $message='Ваш заказ отправлен курьером. Ожидайте доставки. КомуЧё GRILL';             
            }
            elseif($getOrder['shipping_method']=='Бесплатная доставка'){
                $message='Ваш заказ отправлен курьером. Ожидайте доставки. КомуЧё GRILL'; 
            }            
            $getPosterStatusOrder = $this->model_extension_module_checkstatusproduct->setStatusOrder(['poster_status_order'=>'ready','id'=>$vStatusOrder['id']]);
            //$SMSC_API = $this->load->controller('Extension/Module/SMSC_API',['phones'=>$getOrder['telephone'],'message'=>$message]);
            //$log -> write('телефон');
            require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'smsc_api.php');
            $SMSCAPI = new ControllerExtensionModuleSMSCAPI();
            $log -> write($getOrder['telephone'],$getOrder['shipping_method'],$message);
            $log -> write($SMSCAPI->index(['phones'=>$getOrder['telephone'],'message'=>$message]));
            //$log -> write($SMSC_API);
          }  
        }
      }
      elseif($online_check->status==7){
        $log -> write('online чек отменён');
        $getPosterStatusOrder = $this->model_extension_module_checkstatusproduct->setStatusOrder(['poster_status_order'=>'cancell','id'=>$vStatusOrder['id']]); 
        $log -> write($online_check);
      }
      elseif($online_check->status==0){
        $log -> write('online чек не принят');
        
      }
    }
    //$this->response->setOutput($this->load->view('extension/module/checkstatusproduct', $data));
  }
}
?>