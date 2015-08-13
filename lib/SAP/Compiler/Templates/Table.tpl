{$properties}

class SAP_Table_{$moduleName}_{$name} extends SAP_Table_Abstract {

public function __construct() {
$this->setName('{$nameRaw}');
$this->setIsOptional({$isOptional});
{$columns}
}

}
