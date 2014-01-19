<?php
namespace GHist\Adapter;

/**
 * Output adapter for saving history into files
 * It needs in directory and owner jabber ID.
 * Adapter creates directories for each JID and files for each days
 *
 * @package GHist\Adapter
 */
class EjabberdMysql implements AdapterInterface
{
	const TABLE_COLLECTIONS = 'archive_collections';
	const TABLE_MESSAGES = 'archive_messages';

	const DB_CHARSET = 'utf8';

	/**
	 * @var String Your JabberID
	 */
	private $_ownerJid;

	/**
	 * @var string
	 */
	private $_dbConnString;

	/**
	 * @var \mysqli
	 */
	private $_db;

	/**
	 * @param \GHist\HistoryRecord[] $history
	 */
	public function save(array $history)
	{
		if (empty($history)) {
			return;
		}

		\array_reverse($history);

		/**
		 * @var \GHist\HistoryRecord $firstRecord
		 */
		$lastRecord = current($history);
		$firstRecord = end($history);
		$companionJid = $firstRecord->from == $this->_ownerJid ? $firstRecord->to : $firstRecord->from;

		$collId = $this->_createCollections(
			$this->_ownerJid,
			$companionJid,
			date('Y-m-d H:i:s', $firstRecord->date),
			date('Y-m-d H:i:s', $lastRecord->date)
		);

		foreach ($history as $record) {
			$this->_addMessage(
				$collId,
				date('Y-m-d H:i:s', $record->date),
				(int)$record->from == $this->_ownerJid,
				$record->message
			);
		}
	}

	/**
	 * Create collection and return collection ID
	 * @param string $account
	 * @param string $companionJid
	 * @param string $datetime
	 * @return int
	 */
	private function _createCollections($account, $companionJid, $createDatetime, $endDatetime)
	{
		$query = "INSERT INTO " . self::TABLE_COLLECTIONS . "
					(`us`, `with_user`, `with_server`, `with_resource`, `utc`, `change_by`, `change_utc`, `deleted`)
				VALUES
					(?, ?, ?, '', ?, ?, ?, 0)";
		$stmt = $this->_getDb()->prepare($query);
		$withUser = substr($companionJid, 0, strpos($companionJid, '@'));
		$withServer = substr($companionJid, strpos($companionJid, '@')+1);

		$stmt->bind_param('ssssss', $account, $withUser, $withServer, $createDatetime, $account, $endDatetime);

		$stmt->execute();

		return $stmt->insert_id;
	}

	private function _getDb()
	{
		if (!$this->_db) {
			$params = \parse_url('mysqli://' . $this->_dbConnString);
			if (empty($params) || empty($params['host']) || empty($params['path'])) {
				throw new \Exception('Wrong DB connection string');
			}

			$this->_db = new \mysqli(
				$params['host'],
				isset($params['user']) ? $params['user'] : null,
				isset($params['pass']) ? $params['pass'] : null,
				ltrim($params['path'], '/')
			);

			if ($this->_db->connect_errno) {
				throw new \Exception("{$this->_db->connect_errno} {$this->_db->connect_error}");
			}

			$this->_db->set_charset(self::DB_CHARSET);
		}

		return $this->_db;
	}

	private function _addMessage($collId, $datetime, $dir, $message)
	{
		if (!$message) {
			return false;
		}

		$query = "INSERT INTO " . self::TABLE_MESSAGES . "
					(`coll_id`, `utc`, `dir`, `body`)
				VALUES
					(?, ?, ?, ?)";
		$stmt = $this->_getDb()->prepare($query);
		$stmt->bind_param('isis', $collId, $datetime, $dir, $message);

		$stmt->execute();

		return $stmt->insert_id;
	}

	public function getParamsToConfigure()
	{
		return array(
			'dbConnString' => 'DB connection string as user:password@host/dbname',
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
