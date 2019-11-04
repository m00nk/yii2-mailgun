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

	/** @var int код ответа сервера */
	protected $_responseStatusCode;
	/** @var string текст ответа сервера */
	protected $_responseText = '';
	/** @var string текст сообщения об ошибке при фолте */
	protected $_errorMessage = '';

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
		$this->_errorMessage = '';
		$this->_responseStatusCode = null;
		$this->_responseText = '';

		try{
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

			$this->_responseStatusCode = $response->getStatusCode();
			$responseObject = $response->getBody();
			$this->_responseText = (string)$responseObject;

			if($this->_responseStatusCode != 200) {
				$this->_errorMessage = $this->_responseText;
				Yii::error('Mailgun error: response status: '.$this->_responseStatusCode.'. Message: '.$this->_errorMessage, __METHOD__);

				return false;
			}

			Yii::info('Response: '.print_r($this->_responseText, true), __METHOD__);
		} catch(\Exception $e){
			$this->_errorMessage = $e->getMessage();
			Yii::error('Mailgun error:'.$this->_errorMessage, __METHOD__);

			return false;
		}

		return true;
	}

	public function getResponseStatusCode()
	{
		return $this->_responseStatusCode;
	}

	public function getResponseText()
	{
		return $this->_responseText;
	}

	public function getErrorMessage()
	{
		return $this->_errorMessage;
	}
}