<?php

/**
 * Class SAP_Compiler_Generator
 *
 * @author Manuel Will
 * @since 2013
 */
class SAP_Compiler_Generator {

	/**
	 * @var null
	 */
	private $connection = null;

	/**
	 * @var array
	 */
	private $params = array();

	/**
	 * @author Manuel Will
	 * @since 2013
	 */
	public function compile() {
		$compileDirectory = SAP_Factory::get()->getCompileDirectory();
		$this->printOutput('Destination: ' . $compileDirectory);
		$modules = $this->getModules();
		foreach ($modules as $module) {
			$this->params = [];
			$this->printOutput('Generate: ' . $module);
			$data = $this->getConnection()->getMetaData($module);

			foreach ($data as $metaData) {
				switch ($metaData['type']) {
					case 'IMPORT':
						$this->generateImport($module, $metaData);
						break;

					case 'EXPORT':
						$this->generateExport($module, $metaData);
						break;

					case 'TABLE':
						$this->generateTable($module, $metaData);
						break;
				}
			}
			$this->generateModule($module);
		}
	}

	/**
	 * @author Manuel Will <will@redhotmagma.de>
	 * @since  2015-08
	 *
	 * @param $string
	 */
	private function printOutput($string) {
		if (PHP_SAPI === 'cli') {
			echo $string . PHP_EOL;
		}
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 *
	 * @throws SAP_Exception
	 */
	private function generateModule($moduleName) {
		$checkKeys = array(
			'Export',
			'Import',
			'Table'
		);
		foreach ($checkKeys as $key) {
			if (empty($this->params[$key])) {
				$this->params[$key] = array();
			}
		}

		$replacements = array(
			'moduleName' => $this->getClassName($moduleName),
			'nameRaw' => $moduleName,
			'export' => $this->convertArrayToPhpCode($this->params['Export']),
			'import' => $this->convertArrayToPhpCode($this->params['Import']),
			'table' => $this->convertArrayToPhpCode($this->params['Table']),
		);

		$className = __CLASS__;
		$methodName = __FUNCTION__;
		$functionName = str_replace($className . '::', '', $methodName);
		$functionName = str_replace('generate', '', $functionName);
		$template = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Templates/' . $functionName . '.tpl';
		$template = file_get_contents($template);
		$template = $this->replacePlaceholder($template, $replacements);
		$compileDirectory = SAP_Factory::get()->getCompileDirectory();
		$basePath = $compileDirectory . 'SAP/' . $functionName . '/';
		@mkdir($basePath, 0777, true);
		$basePath .= $this->getClassName($moduleName) . '.php';
		file_put_contents($basePath, '<?php' . PHP_EOL . $template);
	}

	/**
	 * @param string $moduleName
	 *
	 * @return string
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getClassName($moduleName) {
		$moduleName = preg_replace_callback('~_([a-z0-9])~i', create_function('$matches', 'return strtoupper($matches[1]);'), strtolower($moduleName));
		$moduleName = ucfirst($moduleName);

		return $moduleName;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 * @param array $metaData
	 * @param string $className
	 * @param string $methodName
	 *
	 * @throws Exception
	 * @throws SAP_Exception
	 */
	private function generate($moduleName, array $metaData, $className, $methodName) {
		$construct = '';
		$doc = array();
		$doc[] = '/**';
		$doc[] = ' *  DO NOT EDIT THIS FILE - IT WAS CREATED BY SAPRFCDAL\'S GENERATOR';
		$doc[] = ' * ';
		foreach ($metaData['def'] as $k => $definition) {
			$definitionName = $definition['name'];
			if (empty($definitionName)) {
				$definitionName = SAP_Meta::NAMELESS;
			}
			$type = 'SAP_Meta::' . $this->getType($definition['abap'], $definition);
			$tmpConstruct = trim('
					$meta = new SAP_Meta();
					$meta->setLength(' . $definition['len'] . ');
					$meta->setType(' . $type . ');
					$this->addColumn(\'' . $definitionName . '\', $meta);
				');
			$tmpConstruct = "\t\t" . preg_replace('~^(\s+)~im', "\t\t", $tmpConstruct) . PHP_EOL . PHP_EOL;
			$construct .= $tmpConstruct;

			switch (true) {
				case $type === SAP_Meta::INTEGER:
				case $type === SAP_Meta::NUMERIC:
					$type = 'int';
					break;

				case $type === SAP_Meta::FLOAT:
					$type = 'float';
					break;

				case $type === SAP_Meta::CHAR:
				case $type === SAP_Meta::HEXDECIMAL:
				case $type === SAP_Meta::DATE:
				case $type === SAP_Meta::TIME:
				case $type === SAP_Meta::PACKETNUMBER:
					$type = 'string';
					break;

				default:
					$type = 'string';
			}

			$doc[] = ' * @property ' . $type . ' $' . $definitionName . ' ' . $type . '(' . $definition['len'] . ')';
		}

		$doc[] = ' */';
		$functionName = str_replace($className . '::', '', $methodName);
		$functionName = str_replace('generate', '', $functionName);
		$template = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Templates/' . $functionName . '.tpl';
		$template = file_get_contents($template);

		$replacements = array(
			'moduleName' => $this->getClassName($moduleName),
			'name' => $this->getClassName($metaData['name']),
			'nameRaw' => $metaData['name'],
			'isOptional' => (1 === (int)$metaData['optional'] ? 'true' : 'false'),
			'columns' => PHP_EOL . rtrim(($construct)),
			'properties' => implode(PHP_EOL, $doc),
		);

		$class = 'SAP_' . $functionName . '_' . $replacements['moduleName'] . '_' . $replacements['name'];
		$this->params[$functionName][$class] = $class;

		$template = $this->replacePlaceholder($template, $replacements);

		$compileDirectory = SAP_Factory::get()->getCompileDirectory();
		$basePath = $compileDirectory . 'SAP/' . $functionName . '/' . $this->getClassName($moduleName);

		@mkdir($basePath, 0777, true);
		$basePath .= '/' . $this->getClassName($metaData['name']) . '.php';
		file_put_contents($basePath, '<?php' . PHP_EOL . $template);
	}

	/**
	 * @param string $source
	 * @param array $replacements
	 *
	 * @return mixed
	 * @author Manuel Will
	 * @since 2013
	 */
	private function replacePlaceholder($source, array $replacements) {
		foreach ($replacements as $searchTerm => $replaceContent) {
			$source = str_replace('{$' . $searchTerm . '}', $replaceContent, $source);
		}

		return $source;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getType($type, $definition) {

		// C, D, F, I, N, P, T, X
		// http://help.sap.com/saphelp_nw04/helpdata/en/fc/eb2fd9358411d1829f0000e829fbfe/content.htm

		switch ($type) {
			case 'C':
				return SAP_Meta::CHAR;

			case 'D':
				return SAP_Meta::DATE; // YYYYMMDD

			case 'N':
				return SAP_Meta::NUMERIC;

			case 'P':
				return SAP_Meta::PACKETNUMBER;

			case 'I':
				return SAP_Meta::INTEGER;

			case 'F':
				return SAP_Meta::FLOAT;

			case 'T':
				return SAP_Meta::TIME; // HHMMSS

			case 'X':
				return SAP_Meta::HEXDECIMAL; // Hexadecimal

				break;

			default:
				print_r($definition);
				throw new Exception('Unknown type: "' . $type . '"');
		}
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 * @param array $metaData
	 */
	private function generateImport($moduleName, array $metaData) {
		$this->generate($moduleName, $metaData, __CLASS__, __METHOD__);
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $moduleName
	 * @param array $metaData
	 */
	private function generateExport($moduleName, array $metaData) {
		$this->generate($moduleName, $metaData, __CLASS__, __METHOD__);
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 */
	private function generateTable($moduleName, array $metaData) {
		$this->generate($moduleName, $metaData, __CLASS__, __METHOD__);
	}

	/**
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getModules() {
		$ymlPath = implode(DIRECTORY_SEPARATOR, [
			EnvironmentVar::get(EnvironmentVar::VENDOR_PATH),
			'cli',
			'modules.yml'
		]);
		$modules = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($ymlPath));
		$modules = array_combine($modules, $modules);

		return $modules;
	}

	/**
	 * @return null|SAP_Connection
	 * @author Manuel Will
	 * @since 2013
	 */
	private function getConnection() {
		if (null == $this->connection) {
			$credentials = SAP_Factory::get()->getCredentials();
			$this->connection = new SAP_Connection($credentials);
		}

		return $this->connection;
	}

	/**
	 * This method returns tabs by given number.
	 *
	 * @param $number
	 *
	 * @return string
	 */
	protected function getTabsByNumber($number) {
		$tabs = '';
		for ($i = 0; $i < $number + 2; ++$i) {
			$tabs .= "\t";
		}

		return $tabs;
	}

	/**
	 * This method is a tool to convert an array to php-code array as string.
	 *
	 * @param array $array
	 * @param bool $quote
	 * @param int $nestingLevel
	 *
	 * @return string
	 */
	protected function convertArrayToPhpCode(array $array, $quote = true, $nestingLevel = 0) {
		if (0 === $nestingLevel && !count($array)) {
			return "array();\n";
		}

		$str = "array(\n";

		foreach ($array as $key => $value) {
			$str .= $this->getTabsByNumber($nestingLevel);
			$str .= "'" . $key . "'\t=> ";
			if (!is_array($value)) {
				if ((int)$value > 0 || false === $quote) {
					$str .= $value . ",\n";
				}
				else {
					$str .= '"' . $value . "\",\n";
				}
			}
			else {
				$str .= $this->convertArrayToPhpCode($value, $quote, $nestingLevel + 1) . "\n";
			}
		}

		$str .= $this->getTabsByNumber($nestingLevel - 1) . ")";

		if (0 === $nestingLevel) {
			$str .= ";\n";
		}
		else {
			$str .= ",\n";
		}

		return $str;
	}

}