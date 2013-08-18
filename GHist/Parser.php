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
			$chats[] = str_replace('&v=c', '&v=pt', $chatLink->href);
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

		$content = $html->find('.maincontent', 0);

		if (!$content) {
			throw new \Exception('Can not find content');
		}

		$history = array();
		foreach ($content->find('table') as $table) {
			$trs = $table->find('tr');
			if (count($trs) < 3) {
				continue;
			}

			$historyRecord = new HistoryRecord();
			$historyRecord->date = \DateTime::createFromFormat(
				'D, M d, Y g:i a',
				str_replace(' at', '', trim($trs[0]->find('td', 1)->plaintext)))->getTimestamp();
			$historyRecord->from = trim($this->_getEmailFromText($trs[0]));
			$historyRecord->to = trim($this->_getEmailFromText($trs[1]));
			$historyRecord->message = trim($trs[2]->plaintext);

			$history[] = $historyRecord;
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
 