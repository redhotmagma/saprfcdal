{$properties}

class SAP_Import_{$moduleName}_{$name} extends SAP_Import_Abstract {

	public function __construct() {
		$this->setName('{$nameRaw}');
		$this->setIsOptional({$isOptional});
		{$columns}
	}

}
