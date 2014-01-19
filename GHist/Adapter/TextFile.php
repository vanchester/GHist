<?php
namespace GHist\Adapter;

/**
 * Output adapter for saving history into files
 * It needs in directory and owner jabber ID.
 * Adapter creates directories for each JID and files for each days
 *
 * @package GHist\Adapter
 */
class TextFile implements AdapterInterface
{
	/**
	 * @var String Directory to save files
	 */
	private $_outputDir;

	/**
	 * @var String Your JabberID
	 */
	private $_ownerJid;

	/**
	 * @param \GHist\HistoryRecord[] $history
	 */
	public function save(array $history)
	{
		foreach ($history as $record) {
			$dir = $this->_outputDir . '/' . ($record->from == $this->_ownerJid ? $record->to : $record->from);
			if (!is_dir($dir)) {
				mkdir($dir, 0700, true);
			}

			$fileName = $dir . '/'. date('Y-m-d', $record->date).'.txt';
			$content = date('H:i:s', $record->date) . ' '
						. $record->from . "\n"
						. html_entity_decode($record->message."\n\n");

			if (file_exists($fileName)) {
				$content .= file_get_contents($fileName);
			}

			file_put_contents($fileName, $content);
		}
	}

	public function getParamsToConfigure()
	{
		return array(
			'outputDir' => 'Output directory',
			'ownerJid' => 'Your google account name'
		);
	}

	public function saveConfiguredParams(array $params = array())
	{
		foreach ($params as $name => $val) {
			$name = '_'.$name;
			$this->$name = $val;
		}
	}

}
