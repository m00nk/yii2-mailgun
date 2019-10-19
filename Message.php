<?php
/**
 * @author Dmitrij "m00nk" Sheremetjev <m00nk1975@gmail.com>
 * Date: 16.10.2019, Time: 1:58 PM
 */

namespace m00nk\mailgun;

use yii\base\NotSupportedException;
use yii\mail\BaseMessage;

class Message extends BaseMessage
{
	protected $data = [];

	protected function processAddress($address)
	{
		return is_array($address) ? (
		''.intval(key($address)) != key($address) ? sprintf('"%s" <%s>', current($address),
			key($address)) : current($address)
		) : $address;
	}

	protected function processManyAdresses($list)
	{
		$out = [];
		if(!is_array($list)) {
			$list = [$list];
		}

		foreach($list as $a1 => $a2) {
			$out[] = $this->processAddress([$a1 => $a2]);
		}

		return $out;
	}

	protected function safeValue($field, $default = [])
	{
		return array_key_exists($field, $this->data) ? $this->data[$field] : $default;
	}

	public function getMessageData()
	{
		return $this->data;
	}

	public function getCharset()
	{
		return null;
	}

	public function setCharset($charset)
	{
		return $this;
	}

	/**
	 * @param array|string $from Либо email, либо [email => name]
	 *
	 * @return BaseMessage
	 */
	public function setFrom($from)
	{
		$this->data['from'] = $this->processAddress($from);

		return $this;
	}

	public function getFrom()
	{
		return $this->safeValue('from', null);
	}

	public function setTo($to)
	{
		$this->data['to'] = $this->processManyAdresses($to);

		return $this;
	}

	public function getTo()
	{
		return $this->safeValue('to');
	}

	public function setReplyTo($replyTo)
	{
		$this->data['h:reply-to'] = $this->processManyAdresses($replyTo);

		return $this;
	}

	public function getReplyTo()
	{
		return $this->safeValue('h:reply-to');
	}

	public function setCc($cc)
	{
		$this->data['cc'] = $this->processManyAdresses($cc);

		return $this;
	}

	public function getCc()
	{
		return $this->safeValue('cc');
	}

	public function setBcc($bcc)
	{
		$this->data['bcc'] = $this->processManyAdresses($bcc);

		return $this;
	}

	public function getBcc()
	{
		return $this->safeValue('bcc');
	}

	public function setSubject($subject)
	{
		$this->data['subject'] = $subject;

		return $this;
	}

	public function getSubject()
	{
		return $this->safeValue('subject', null);
	}

	public function setTextBody($text)
	{
		$this->data['text'] = $text;

		return $this;
	}

	public function setHtmlBody($html)
	{
		$this->data['html'] = $html;

		return $this;
	}

	public function attach($filePath, array $options = [])
	{
		$this->data['attachment'][] = [
			'contents' => $filePath,
			'filename' => (isset($options['fileName']) ? $options['fileName'] : null),
		];

		return $this;
	}

	public function attachContent($content, array $options = [])
	{
		throw new NotSupportedException('attach content is not supported');
	}

	public function embed($filePath, array $options = [])
	{
		$this->data['inline'][] = [
			'contents' => $filePath,
			'filename' => (isset($options['fileName']) ? $options['fileName'] : null),
		];

		return null;
	}

	public function embedContent($content, array $options = [])
	{
		throw new NotSupportedException('embed content is not supported');
	}

	public function toString()
	{
		return print_r($this->data, true);
	}

	public function setDkim($enabled)
	{
		$this->data['o:dkim'] = $enabled ? 'yes' : 'no';
		return $this;
	}

	public function getDkim()
	{
		return $this->safeValue('o:dkim', 'no') == 'yes';
	}

	public function setTestMode($enabled)
	{
		$this->data['o:testmode'] = $enabled ? 'yes' : 'no';
		return $this;
	}

	public function getTestMode()
	{
		return $this->safeValue('o:testmode', 'no') == 'yes';
	}
}