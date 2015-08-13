{$properties}

class SAP_Export_{$moduleName}_{$name} extends SAP_Export_Abstract {

	public function __construct() {
		$this->setName('{$nameRaw}');
		$this->setIsOptional({$isOptional});
		{$columns}
	}

}
