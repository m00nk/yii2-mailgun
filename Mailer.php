<?php
/**
 * @author Dmitrij "m00nk" Sheremetjev <m00nk1975@gmail.com>
 * Date: 16.10.2019, Time: 1:37 PM
 */

namespace m00nk\mailgun;

use GuzzleHttp\Client;
use Yii;
use yii\mail\BaseMailer;

class Mailer extends BaseMailer
{
	/** @var string private API-key. Получать там: https://app.mailgun.com/app/account/security/api_keys */
	public $key;

	/** @var string domain name */
	public $domain;

	public $testMode = false;

	/** @var bool использовать энд-поинт для США (TRUE) или Европы (FALSE) */
	public $usaCustomer = true;

	//-----------------------------------------
	public $messageClass = 'm00nk\mailgun\Message';

	protected $endPointUsa = 'https://api.mailgun.net/v3'; // for USA customers
	protected $endPointEu = 'https://api.eu.mailgun.net/v3'; // for USA customers

	protected function getEndpoint()
	{
		return $this->usaCustomer ? $this->endPointUsa : $this->endPointEu;
	}

	/**
	 * @param Message $message
	 *
	 * @return bool
	 */
	protected function sendMessage($message)
	{
		try{
			$address = $message->getTo();
			if(is_array($address)) {
				$address = implode(', ', $address);
			}

			Yii::info('Sending email "'.$message->getSubject().'" to "'.$address.'"', __METHOD__);

			if($this->testMode) {
				$message->setTestMode(true);
			}

			//-----------------------------------------
			// собираем пакет данных для поста
			$mp = [];
			foreach($message->getMessageData() as $k => $v) {
				if(!is_array($v)) {
					$v = [$v];
				}

				if(in_array($k, ['attachment', 'inline'])) {
					foreach($v as $vv) {
						$item = ['name' => $k];
						foreach($vv as $vkk => $vvv) {

							if($vkk == 'contents') {
								$vvv = fopen($vvv, 'r');
							}

							$item[$vkk] = $vvv;
						}
						$mp[] = $item;
					}
				}
				else {
					foreach($v as $vk => $vv) {
						$item = ['name' => $k];

						if(is_integer($vk)) {
							$vk = 'contents';
						}

						$item[$vk] = $vv;
						$mp[] = $item;
					}
				}
			}

			//-----------------------------------------
			$http = new Client();

			$url = $this->getEndpoint().'/'.$this->domain.'/messages';
			$auth = ['api', $this->key, 'basic'];

			$response = $http->post($url, [
				'auth' => $auth,
				'multipart' => $mp,
			]);

			$statusCode = $response->getStatusCode();
			$responseObject = $response->getBody();
			$responseText = (string)$responseObject;

			if($statusCode != 200) {

				Yii::error('Mailgun error: response status: '.$statusCode.'. Message: '.$responseText, __METHOD__);

				return false;
			}

			Yii::info('Response: '.print_r($responseText, true), __METHOD__);
		} catch(\Exception $e){
			Yii::error('Mailgun error:'.$e->getMessage(), __METHOD__);

			return false;
		}

		return true;
	}
}