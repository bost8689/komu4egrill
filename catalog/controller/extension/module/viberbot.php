<?php
//require_once("../vendor/autoload.php");
//require_once(DIR_MAIN . 'vendor/autoload.php');
//use Viber\Client;
class ControllerExtensionModuleViberbot extends Controller {
	public function index() {
		echo VIBER_TOKEN;
		
		// $apiKey = '4b93ef200b67dd34-7529da8704343d91-b055db508f614d0f'; // <- PLACE-YOU-API-KEY-HERE
		// $webhookUrl = 'https://viber.hcbogdan.com/bot.php'; // <- PLACE-YOU-HTTPS-URL
		// try {
		//     $client = new Client([ 'token' => $apiKey ]);
		//     $result = $client->setWebhook($webhookUrl);
		//     echo "Success!\n";
		// } catch (Exception $e) {
		//     echo "Error: ". $e->getError() ."\n";
		// }

		}
}//class
?>