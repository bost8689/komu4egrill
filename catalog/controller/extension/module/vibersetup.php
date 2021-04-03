<?php
//require_once("../vendor/autoload.php");
require_once(DIR_MAIN . 'vendor/autoload.php');
use Viber\Client;
class ControllerExtensionModuleViberbot extends Controller {
	public function index() {
		
		$apiKey = VIBER_TOKEN; // <- PLACE-YOU-API-KEY-HERE

		$webhookUrl = 'https://komu4egrill.ru/index.php?route=extension/module/viberbot'; // <- PLACE-YOU-HTTPS-URL
		try {
		    $client = new Client([ 'token' => $apiKey ]);
		    $result = $client->setWebhook($webhookUrl);
		    echo "Success!\n";
		} catch (Exception $e) {
		    echo "Error: ". $e->getError() ."\n";
		}

		}
}//class
?>