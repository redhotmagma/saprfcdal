<?php

/**
 * Class SAP_Module_Abstract
 *
 * @author Manuel Will
 * @since 2013
 */
abstract class SAP_Module_Abstract {

	/**
	 * @var array
	 */
	private $exporter = array();

	/**
	 * @var array
	 */
	private $importer = array();

	/**
	 * @var array
	 */
	private $tables = array();

	/**
	 * @var null
	 */
	private $connection = null;

	/**
	 * @var null
	 */
	private $moduleName = null;

	/**
	 * @var SAP_Table_Abstract[]
	 */
	private $dataTable = array();

	/**
	 * @var SAP_Import_Abstract[]
	 */
	private $dataImport = array();



	/**
	 * @param null $moduleName
	 */
	protected function setModuleName( $moduleName ) {
		$this->moduleName = (string) $moduleName;
	}



	/**
	 * @return null
	 */
	public function getModuleName() {
		return $this->moduleName;
	}



	/**
	 * @return null|SAP_Connection
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getConnection() {
		if( null === $this->connection ) {
			$credentials = SAP_Factory::get()->getCredentials();
			$this->connection = new SAP_Connection( $credentials );
		}

		return $this->connection;
	}



	/**
	 * @param array $exporter
	 */
	protected function setExporter( $exporter ) {
		$this->exporter = $exporter;
	}



	/**
	 * @return array
	 */
	public function getExporter() {
		return $this->exporter;
	}



	/**
	 * @param array $importer
	 */
	protected function setImporter( $importer ) {
		$this->importer = $importer;
	}



	/**
	 * @return array
	 */
	protected function getImporter() {
		return $this->importer;
	}



	/**
	 * @param array $tables
	 */
	protected function setTables( $tables ) {
		$this->tables = $tables;
	}



	/**
	 * @return array
	 */
	public function getTables() {
		return $this->tables;
	}



	/**
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function get() {
		$this->getConnection()->reset( $this );
		foreach( $this->dataImport as $import ) {
			$this->getConnection()->setImport( $this->getModuleName(), $import );
		}

		foreach( $this->dataTable as $table ) {
			$this->getConnection()->setAppendTable( $this->getModuleName(), $table );
		}

		return $this->getConnection()->executeRead( $this );
	}



	/**
	 * @author Manuel Will
	 * @since 2014-05
	 */
	public function closeConnection() {

		if( null !== $this->connection ) {
			$this->getConnection()->closeConnection();
			$this->connection = null;
		}
	}



	/**
	 * @param bool $compressed
	 *
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function getDebug( $compressed = false ) {
		$debug = $this->getConnection()->getDebug( $compressed );

		return $debug;
	}



	/**
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function save() {
		$this->getConnection()->reset( $this );
		foreach( $this->dataImport as $import ) {
			$this->getConnection()->setImport( $this->getModuleName(), $import );
		}

		foreach( $this->dataTable as $table ) {
			$this->getConnection()->setTable( $this->getModuleName(), $table );
		}

		return $this->getConnection()->executeSave( $this );
	}

	/**
	 * @param SAP_Import_Abstract $import
	 * @param bool $disabledAutoX
	 *
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	public function addImport( SAP_Import_Abstract $import, $disabledAutoX = false ) {
		$className = get_class( $import );
		if( ! isset( $this->importer[$className] ) ) {
			throw new Exception( 'Not allowed importer instance: ' . $className . ' for module: ' . get_class( $this ) );
		}

		$importClassXName = $className . 'x';
		$classImportXExists = class_exists( $importClassXName );
		if( true === $classImportXExists && false === $disabledAutoX ) {
			$importerClassX = new $importClassXName();
			$arrData = $import->getDataRaw();
			foreach( array_keys( $arrData ) as $key ) {
				try {
					$importerClassX->{$key} = 'X';
				}
				catch( Exception $e ) {
					// silent catch
				}
			}

			if( ! isset( $this->dataImport[$importClassXName] ) ) {
				$this->dataImport[$importClassXName] = $importerClassX;
			}
		}

		if( ! isset( $this->dataImport[$className] ) ) {
			$this->dataImport[$className] = $import;
		}
	}

	/**
	 * @param SAP_Table_Abstract $table
	 * @param bool $disabledAutoX
	 *
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	public function addTable( SAP_Table_Abstract $table, $disabledAutoX = false ) {
		$className = get_class( $table );

		if( ! isset( $this->tables[$className] ) ) {
			throw new Exception( 'Not allowed table instance: ' . $className . ' for module: ' . get_class( $this ) );
		}

		$tableClassXName = $className . 'x';
		$classTableXExists = class_exists( $tableClassXName );
		if( true === $classTableXExists && false === $disabledAutoX ) {
			$tableClassX = new $tableClassXName();
			$arrData = $table->getDataRaw();
			foreach( array_keys( $arrData ) as $key ) {
				try {
					$tableClassX->{$key} = 'X';
				}
				catch( Exception $e ) {
					// silent catch
				}
			}

			if( ! isset( $this->dataTable[$tableClassXName] ) ) {
				$this->dataTable[$tableClassXName] = $tableClassX;
			}
		}

		if( ! isset( $this->dataTable[$className] ) ) {
			$this->dataTable[$className] = $table;
		}
	}
}