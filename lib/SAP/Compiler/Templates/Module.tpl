class SAP_Module_{$moduleName} extends SAP_Module_Abstract {

	public function __construct() {
		$import = {$import}
		$export = {$export}
		$table = {$table}

		$this->setModuleName('{$nameRaw}');
		$this->setImporter($import);
		$this->setExporter($export);
		$this->setTables($table);
	}

}
