<?php
namespace GHist\Adapter;

interface AdapterInterface
{
	/**
	 * @param array $history
	 * @return \GHist\HistoryRecord[]
	 */
	public function save(array $history);

	/**
	 * @return array [name => hint]
	 */
	public function getParamsToConfigure();

	/**
	 * @param array [name => value]
	 */
	public function saveConfiguredParams(array $params = array());
}
