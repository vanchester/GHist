<?php
namespace GHist;

require_once __DIR__.'/../vendor/simple_html_dom.php';

class Parser
{
    public function getLoginFormParams($htmlContent)
	{
		$html = str_get_html($htmlContent);

		$form = $html->find('#gaia_loginform', 0);

		if (!$form) {
			throw new \Exception('Can not find login form in answer');
		}

		$params = array();
		foreach ($form->find('input') as $input) {
			$params[$input->name] = $input->value;
		}

		return $params;
	}

	public function getLabels($htmlContent)
	{
		$html = str_get_html($htmlContent);

		$td = $html->find('.lb', 0);
		if (!$td) {
			throw new \Exception('Can not find labels table');
		}

		$labels = array();
		foreach ($td->find('a') as $label) {
			if (!strpos($label->href, '&l=')) {
				continue;
			}

			$labels[] = array(
				'href' => $label->href,
				'name' => html_entity_decode($label->plaintext)
			);
		}

		if (!$labels) {
			throw new \Exception('Can not find labels');
		}

		return $labels;
	}

	public function getChains($htmlContent)
	{
		$html = str_get_html($htmlContent);

		$form = $html->find('form[name="f"]', 0);

		if (!$form) {
			throw new \Exception('Can not find list of chats');
		}

		$chats = array();
		foreach ($form->find('table', 1)->find('a') as $chatLink) {
			$chats[] = \html_entity_decode($chatLink->href).'&dhm=1&d=e';
		}

		return $chats;
	}

	/**
	 * @param $htmlContent
	 * @return HistoryRecord[]
	 * @throws \Exception
	 */
	public function parseHistory($htmlContent)
	{
		$html = str_get_html($htmlContent);

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();
		$dom->loadHTML($html);
		$xpath = new \DOMXPath($dom);

		$history = array();

		/**
		 * @var\ DOMNodeList $nodes
		 * /html/body/table[2]/tbody/tr/td[2]/table[1]/tbody/tr/td[2]/table[4]/tbody/tr/td/table
		 */
		$nodes = $xpath->query('/html/body/table[2]/tr[1]/td[2]/table[1]/tr/td[2]/table[*]/tr/td/table');
		foreach ($nodes as $num => $node) {

			$historyRecord = new HistoryRecord();

			/**
			 * @var \DOMNode $node
			 */
			if ($num == 0) {
				continue;
			}

			foreach ($node->childNodes as $childNum => $childNode) {
				switch ($childNum) {
					case 0:
						foreach ($childNode->childNodes as $subChildNum => $subChildNode) {
							switch ($subChildNum) {
								case 0:
									$historyRecord->from = $this->_getEmailFromText($subChildNode->textContent);
									break;
								case 2:
									$historyRecord->date = \DateTime::createFromFormat(
										'D, M d, Y g:i a',
										str_replace(' at', '', trim($subChildNode->textContent))
									)->getTimestamp();
									break;
							}
						}
						break;
					case 1:
						$historyRecord->to = $this->_getEmailFromText($childNode->textContent);
						break;
					case 3:
						$historyRecord->message = $childNode->textContent;
						break;
				}
			}

			if (!empty($historyRecord->message)) {
				$history[] = $historyRecord;
			}
		}

		return array_reverse($history);
	}

	private function _getEmailFromText($text)
	{
		if (preg_match('/([\w\d\.\-\_]+@[\w\d\.\-\_]+)/', $text, $matches)) {
			return $matches[1];
		}

		return null;
	}
}
