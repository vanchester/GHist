<?php
namespace GHist;

require __DIR__.'/../vendor/autoload.php';

use \Guzzle\Http\Client;
use \Guzzle\Plugin\Cookie\CookiePlugin;
use \Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use \GHist\Adapter\AdapterInterface;

class Steps
{
	/**
	 * @var Parser
	 */
	private $_parser;

	/**
	 * @var \Guzzle\Http\Client
	 */
	private $_client;

	/**
	 * @var \Guzzle\Http\Message\Response
	 */
	private $_response;

	/**
	 * @var AdapterInterface
	 */
	private $_adapter;

	private $_labels = [];

	private $_selectedLabelNum = -1;

	public function __construct(AdapterInterface $adapter)
	{
		$this->_adapter = $adapter;

		$this->_parser = new Parser();
		$cookiePlugin = new CookiePlugin(new ArrayCookieJar());

		$this->_client = new Client('https://gmail.com');
		$this->_client->addSubscriber($cookiePlugin);

		$this->_client->setUserAgent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19');
	}

    public function showWelcomeMsg()
	{
		echo "\nGTalk history parser, v1.0\n";
	}

	public function login()
	{
		echo "\n- Log in into your Google account\n";

		$this->_response = $this->_client->get('/')->send();

		$formParams = $this->_parser->getLoginFormParams($this->_response->getBody());

		$redirect = false;

		while (!$redirect) {
			echo "Enter email: ";
			$formParams['Email'] = trim(fgets(STDIN));

			echo "Enter password: ";
			$formParams['Passwd'] = trim(fgets(STDIN));

			$request = $this->_client->post('https://accounts.google.com/ServiceLoginAuth', array(), $formParams);
			$this->_response = $request->send();

			$this->_response = $this->_client->get($this->_response->getEffectiveUrl().'&ui=html')->send();

			$redirect = $this->_response->isRedirect() || strpos($this->_response->getBody(true), 'nvp_a_arch');
			if (!$redirect) {
				echo "Wrong email or password. Try again\n";
			}

			while ($this->_response->isRedirect()) {
				$url = $this->_response->getHeader('location');
				$client = new Client($url);
				$this->_response = $client->get('/')->send();
			}
		}
	}

	public function selectLabel()
	{
		echo "\n- Searching labels list\n";

		$this->_labels = $this->_parser->getLabels($this->_response->getBody());

		echo "Founded labels:\n";
		foreach ($this->_labels as $key => $label) {
			echo ($key + 1) . ": {$label['name']}\n";
		}

		while (!isset($this->_labels[$this->_selectedLabelNum])) {
			echo "Enter the number of label with chats: ";
			$this->_selectedLabelNum = trim(fgets(STDIN)) - 1;
		}
	}

	public function exportHistory()
	{
		$path = parse_url($this->_response->getEffectiveUrl())['path'];

		$this->_response = $this->_client->get("https://mail.google.com/{$path}/{$this->_labels[$this->_selectedLabelNum]['href']}")->send();

		echo "\n- Parsing pages with chains of messages\n";

		$chains = array();
		$st = 0;
		$i = 1;
		echo "\n";
		do {
			echo "\rparsing page {$i}";
			$url = "https://mail.google.com/{$path}/{$this->_labels[$this->_selectedLabelNum]['href']}" . '&st=' . $st;
			$response = $this->_client->get($url)->send();
			$chainsFromPage = $this->_parser->getChains($response);
			$chains = array_merge($chains, $chainsFromPage);
			$st += 50;
			$i++;
			sleep(1);
		} while ($chainsFromPage);
		echo "\n";

		$totalChains = count($chains);
		echo "{$totalChains} chain(s) founded\n";

		echo "\n - Parsing history (sleep 3 minutes every 10 chain)\n";

		foreach ($chains as $key => $getParams) {
			$this->_showStatus($key + 1, $totalChains);

			$response = $this->_client->get("https://mail.google.com/{$path}/".$getParams)->send();
			$history = $this->_parser->parseHistory($response->getBody());

			$this->_adapter->save($history);

			$key + 1 % 10 ? sleep(1) : sleep(60 * 3);
		}
	}

	/**
	 * Copyright (c) 2010, dealnews.com, Inc.
	 */
	private function _showStatus($done, $total, $size=80)
	{
		static $start_time;

		// if we go over our bound, just ignore it
		if($done > $total) return;

		if(empty($start_time)) $start_time=time();
		$now = time();

		$perc=(double)($done/$total);

		$bar=floor($perc*$size);

		$status_bar="\r[";
		$status_bar.=str_repeat("=", $bar);
		if($bar<$size){
			$status_bar.=">";
			$status_bar.=str_repeat(" ", $size-$bar);
		} else {
			$status_bar.="=";
		}

		$disp=number_format($perc*100, 0);

		$status_bar.="] $disp%  $done/$total";

		$rate = ($now-$start_time)/$done;
		$left = $total - $done;
		$eta = round($rate * $left, 2);

		$elapsed = $now - $start_time;

		$status_bar.= " remaining: ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";

		echo "$status_bar  ";

		flush();

		// when done, send a newline
		if($done == $total) {
			echo "\n";
		}
	}
}
 