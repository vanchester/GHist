<?php
namespace GHist\Adapter;

interface AdapterInterface
{
	/**
	 * @param array $history
	 * @return \GHist\HistoryRecord[]
	 */
	public function save(array $history);

}
 