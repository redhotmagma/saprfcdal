<?php

/**
 * Class SAP_Connection
 *
 * @author Manuel Will
 * @since 2013
 */
class SAP_Connection {

	private static $lastDebug = array();

	/**
	 * @var array
	 */
	private $arrCredentials = array();

	/**
	 * @var null
	 */
	private static $connectionResource = null;

	/**
	 * @var array
	 */
	private $debug = array();

	/**
	 * @var array
	 */
	private $debugCompressed = array();

	/**
	 * @var int
	 */
	private $tableLoop = array();

	/**
	 * @var null
	 */
	private $moduleResource = null;

	/**
	 * @param array $arrCredentials
	 */
	public function __construct(array $arrCredentials) {
		$this->arrCredentials = $arrCredentials;
		$this->getConnectionResource();
	}

	/**
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public static function getLastDebug() {
		return self::$lastDebug;
	}

	/**
	 * @return null
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getConnectionResource() {
		if (null === self::$connectionResource) {
			$this->checkSapIsConnectAble();
			$connection = saprfc_open($this->arrCredentials);
			if (!$connection) {
				$arrCredentials = $this->arrCredentials;
				$message = sprintf('The SAP-Connection failed. %s@%s ', $arrCredentials['ASHOST'], $arrCredentials['USER']);
				throw new SAP_Exception($message);
			}

			self::$connectionResource = $connection;
		}

		return self::$connectionResource;
	}

	/**
	 * @author Manuel Will <will@redhotmagma.de>
	 * @since  2015-06
	 */
	private function checkSapIsConnectAble() {

		/**
		 * SAP-Info: http://help.sap.com/saphelp_46c/helpdata/de/6d/2a41373c1ede6fe10000009b38f936/content.htm
		 * RFC (Remote Funtion Call) ist eine SAP-eigene Kommunikationsschnittstelle.
		 * Bei der RFC Kommunikation gibt es immer einen Aufrufenden (RFC-Client) und einen Aufgerufenen (RFC-Server).
		 * Der RFC-Server stellt einen oder mehrere Funktionsbausteine zum Aufruf zur Verfügung.
		 * Ein RFC-Client kann solch einen Funktionsbaustein aufrufen, Daten übergeben und die Ergebnisse des Funktionsbausteins zurücklesen.
		 * Hierbei können externe Programme und das SAP-System sowohl den Part des Servers, wie auch des Clients übernehmen.
		 * Das Gateway kann auch für die Kommunikation zwischen zwei Anwendungen innerhalb eines SAP-Systems eingesetzt werden.
		 * Das SAP-Gateway ist auf jedem Applikationsserver unter dem TCP-Port sapgw<nr> zu erreichen. Hierbei ist <nr> die Instanznummer der Applikationsinstanz.
		 * Übliche Standardwerte für Instanznummer 00: SAP Gateway Port: sapgw00 3300/TCP
		 */
		$strHost = $this->arrCredentials['ASHOST'];
		$intPort = 3300;
		$errorReportingLevel = error_reporting(0);
		$blnSuccess = fsockopen($strHost, $intPort, $errorCode, $errorString, 5);
		error_reporting($errorReportingLevel);
		if (!$blnSuccess && !empty($errorString)) {
			$strError = sprintf('SAP-Server %s is not available. %s', $strHost, $errorString);
			throw new SAP_Exception($strError, SAP_Exception::TIMEOUT);
		}
	}

	/**
	 * @author Manuel Will
	 * @since 2014-05
	 */
	public function closeConnection() {
		$objConnectionResource = $this->getConnectionResource();
		if (!empty($objConnectionResource)) {
			saprfc_close($objConnectionResource);
			self::$connectionResource = null;
		}
	}

	/**
	 * @param $moduleName
	 *
	 * @return mixed
	 * @author Manuel Will
	 * @since 2013
	 */
	public function getMetaData($moduleName) {
		$moduleConnection = saprfc_function_discover($this->getConnectionResource(), $moduleName);
		$data = saprfc_function_interface($moduleConnection);

		return $data;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 * @param SAP_Import_Abstract $import
	 */
	public function setImport($moduleName, SAP_Import_Abstract $import) {
		$importName = $import->getName();
		$dataSap = $this->getDataFromClass($import);

		$this->debug['import'][$importName]['parameters'] = $dataSap;
		$this->debugCompressed['import'][$importName]['parameters'] = $this->compressData($dataSap);
		$state = saprfc_import($this->getModuleResource($moduleName), $importName, $dataSap);
		$this->debug['import'][$importName]['successfully'] = (bool)$state;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 * @param SAP_Table_Abstract $table
	 */
	public function setTable($moduleName, SAP_Table_Abstract $table) {
		$tableName = $table->getName();
		$dataSap = $this->getDataFromClass($table);

		if (!isset($this->tableLoop[$tableName])) {
			$this->tableLoop[$tableName] = 1;
		}

		$loop = &$this->tableLoop[$tableName];

		$this->debug['table'][$tableName]['parameters'][$loop] = $dataSap;
		$this->debugCompressed['table'][$tableName]['parameters'][$loop] = $this->compressData($dataSap);
		$state = saprfc_table_insert($this->getModuleResource($moduleName), $tableName, $dataSap, $this->tableLoop);
		$this->debug['table'][$tableName]['states'][$loop]['successfully'] = (bool)$state;
		$loop++;
	}

	/**
	 * @param $data
	 *
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	private function compressData($data) {
		if (!is_array($data)) {
			return $data;
		}

		foreach ($data as $key => $value) {
			$value = trim($value);
			if (empty($value)) {
				unset($data[$key]);
			}
		}

		return $data;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 * @param SAP_Table_Abstract $table
	 */
	public function setAppendTable($moduleName, SAP_Table_Abstract $table) {
		$tableName = $table->getName();
		$dataSap = $this->getDataFromClass($table);
		$this->debug['tableAppend'][$tableName]['parameters'] = $dataSap;
		$this->debugCompressed['tableAppend'][$tableName]['parameters'] = $this->compressData($dataSap);
		$state = saprfc_table_append($this->getModuleResource($moduleName), $tableName, $dataSap);
		$this->debug['tableAppend'][$tableName]['successfully'] = (bool)$state;
	}

	/**
	 * @param SAP_Abstract $abstract
	 *
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getDataFromClass(SAP_Abstract $abstract) {
		$result = $abstract->getDataRaw();

		if (count($result) === 1 && isset($result[SAP_Meta::NAMELESS])) {
			$result = $result[SAP_Meta::NAMELESS];
		}

		return $result;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param SAP_Module_Abstract $module
	 */
	public function reset(SAP_Module_Abstract $module) {
		$this->debug = array('Funktions-Baustein' => $module->getModuleName());
		$this->debugCompressed = array('Funktions-Baustein' => $module->getModuleName());
		$this->tableLoop = array();
		$this->moduleResource = null;
		foreach (array_keys($module->getTables()) as $tableName) {
			@saprfc_table_init($this->getModuleResource($module->getModuleName()), $this->getLastClassPrefix($tableName));
		}
	}

	/**
	 * @param bool $compressed
	 *
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function getDebug($compressed = false) {
		if (true === $compressed) {
			return $this->debugCompressed;
		}

		return $this->debug;
	}

	/**
	 * @param $classNameSpace
	 *
	 * @return mixed
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getLastClassPrefix($classNameSpace) {
		$explode = explode('_', $classNameSpace);
		$result = $explode[count($explode) - 1];
		$result = preg_replace('~([A-Z])([A-Z])~', '$1_$2', $result);
		$result = preg_replace('~([a-z])([A-Z])~', '$1_$2', $result);
		$result = strtoupper($result);

		return $result;
	}

	/**
	 * @param $moduleName
	 *
	 * @return mixed
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getModuleResource($moduleName) {
		if (null === $this->moduleResource) {
			$this->moduleResource = saprfc_function_discover($this->getConnectionResource(), $moduleName);
		}

		return $this->moduleResource;
	}

	/**
	 * @param SAP_Module_Abstract $module
	 *
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function executeRead(SAP_Module_Abstract $module) {
		$this->storeDebugTraceToStatic();

		$moduleName = $module->getModuleName();
		$moduleResource = $this->getModuleResource($moduleName);
		$receivedData = saprfc_call_and_receive($moduleResource);

		$this->checkStatusCode($receivedData, $moduleResource);

		$result = array();
		$tables = array();
		foreach (array_keys($module->getTables()) as $tableName) {
			$tableName = $this->getLastClassPrefix($tableName);
			$tables[$tableName] = $tableName;
			$rows = saprfc_table_rows($moduleResource, $tableName);
			if ($rows > 0) {
				for ($i = 1; $i <= $rows; $i++) {
					$result[$tableName][] = saprfc_table_read($moduleResource, $tableName, $i);
				}
			}
		}

		if (empty($result) && empty($tables)) {
			$return = saprfc_export($moduleResource, "RETURN");
			$this->checkReturnError($return);
		}

		if (count($result) === 1 && isset($result['RETURN'][0]['TYPE']) && $result['RETURN'][0]['TYPE'] == 'E') {
			$this->checkReturnError($result['RETURN'][0]);
		}

		$this->appendExporter($result, $moduleResource, $module->getExporter());

		//Debug info
		//		saprfc_function_debug_info($moduleConnection);
		saprfc_function_free($moduleResource);

		return $result;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 */
	public function storeDebugTraceToStatic() {
		self::$lastDebug = $this->debugCompressed;
	}

	/**
	 */
	private function appendExporter(&$result, $moduleResource, array $exporter) {
		foreach ($exporter as $exporterName) {
			$exportIndex = $this->getLastClassPrefix($exporterName);
			$additionalExportParam = saprfc_export($moduleResource, $exportIndex);
			if (!empty($additionalExportParam)) {
				$result[$exportIndex] = $additionalExportParam;
			}
		}
	}

	/**
	 * @param SAP_Module_Abstract $module
	 *
	 * @throws Exception
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function executeSave(SAP_Module_Abstract $module) {
		$this->storeDebugTraceToStatic();

		$moduleName = $module->getModuleName();
		$moduleResource = $this->getModuleResource($moduleName);
		$receivedData = @saprfc_call_and_receive($moduleResource);
		$this->checkStatusCode($receivedData, $moduleResource);

		/**
		 * Ein Commit kann trotzdem stattfinden, obwohl ein Resultat zurückkommt.
		 * Ein Rollback braucht es eigentlich nur beim Fehler, der wirklich ein Fehler ist. Der Fehler würde dann aber schon
		 * in $this->checkStatusCode eine Exception werfen. Möglicherweise erübrigt sich ein Rollback eigentlich immer. Hier sollte wohl immer
		 * Array zurückkommen weshalb das nur mit if ($var) geprüft wurde. Bei manchen Bausteinen kommt es wohl vor, dass auch -1 zurück kommen kann.
		 * Das heißt, es kommt ein "Fehlerresult" zurück, aber nicht als Array sondern als "-1". Das ist offensichtlich kein Fehler.

		 */
		$mxdResult = saprfc_table_rows($moduleResource, 'RETURN');
		if ($mxdResult && $mxdResult != -1) {
			$moduleResource2 = saprfc_function_discover($this->getConnectionResource(), 'BAPI_TRANSACTION_ROLLBACK');
			$errorCode = saprfc_call_and_receive($moduleResource2);
			$this->checkStatusCode($errorCode, $moduleResource2);
		}
		else {
			$moduleResource2 = saprfc_function_discover($this->getConnectionResource(), 'BAPI_TRANSACTION_COMMIT');
			$errorCode = saprfc_call_and_receive($moduleResource2);
			$this->checkStatusCode($errorCode, $moduleResource2);
		}

		$successState = @saprfc_table_read($moduleResource, 'RETURN', 1);
		//
		if (!empty($successState['TYPE']) && $successState['TYPE'] == 'E') {
			throw new SAP_Exception(json_encode($successState));
		}

		$result = array();
		$this->appendExporter($result, $moduleResource, $module->getExporter());

		//		saprfc_function_debug_info($moduleConnection);
		saprfc_function_free($moduleResource);

		if (PHP_SAPI == 'cli') {
			echo print_r($this->debug) . PHP_EOL;
		}

		return array(
			'transactionSuccess' => false === $successState,
			'export' => $result
		); // d.h. insert hat funktioniert!
	}

	/**
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	private function checkStatusCode($receivedData, $moduleConnection) {
		if ($receivedData !== SAPRFC_OK) {
			if ($this->getConnectionResource() == SAPRFC_EXCEPTION) {
				$errorMessage = saprfc_exception($moduleConnection);
			}
			else {
				$errorMessage = saprfc_error();
			}

			if (!empty($errorMessage)) {
				throw new SAP_Exception($errorMessage);
			}
		}
	}

	/**
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	private function checkReturnError($arrError) {
		$strMessage = PHP_EOL;
		if (is_array($arrError) && !empty($arrError['TYPE']) && $arrError['TYPE'] == 'E') {
			foreach ($arrError as $key => $value) {
				$strMessage .= $key . ' => ' . $value . PHP_EOL;
			}
			throw new SAP_Exception($strMessage);
		}
	}

}