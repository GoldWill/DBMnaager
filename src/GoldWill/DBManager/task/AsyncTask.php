<?php
namespace GoldWill\DBManager\task;

use GoldWill\DBManager\ConnectionConfig;
use GoldWill\DBManager\DBManager;
use GoldWill\DBManager\Timings;
use pocketmine\scheduler\AsyncTask as PMAsyncTask;
use pocketmine\Server;


abstract class AsyncTask extends PMAsyncTask
{
	/** @var DBManager|null */
	protected $DBManager = null;
	
	/** @var ConnectionConfig|string */
	protected $connectionConfig;
	
	
	/**
	 * AsyncTask constructor.
	 *
	 * @param DBManager $DBManager
	 * @param ConnectionConfig $connectionConfig
	 */
	protected function __construct(DBManager $DBManager, ConnectionConfig $connectionConfig)
	{
		$this->connectionConfig = serialize($connectionConfig);
	}
	
	public function onRun()
	{
		try
		{
			$this->connectionConfig = unserialize($this->connectionConfig);
			
			$this->handleRun();
		}
		finally
		{
			$this->connectionConfig = null;
		}
	}
	
	public function onCompletion(Server $server)
	{
		Timings::$asyncComplete->startTiming();
		
		$DBManager = null;
		
		try
		{
			$DBManager = DBManager::getInstance($server);
			
			$this->handleCompetition($DBManager);
		}
		catch (\Exception $e)
		{
			if ($DBManager === null)
			{
				Timings::$asyncComplete->stopTiming();
				throw $e;
			}
			
			$DBManager->getLogger()->error('Error when in handleCompletion: ' . $e->getMessage());
		}
		finally
		{
			$DBManager = null;
		}
		
		Timings::$asyncComplete->stopTiming();
	}
	
	protected abstract function handleRun();
	protected abstract function handleCompetition(DBManager $DBManager);
}