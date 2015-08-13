<?php

/**
 * Class SAP_Abstract
 *
 * @author Manuel Will
 * @since 2013
 */
abstract class SAP_Abstract {

	/**
	 * @var SAP_Meta[]
	 */
	protected $columns = array();

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var bool
	 */
	protected $isOptional = true;

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @throws Exception
	 */
	public function __set($name, $value) {
		$this->assertField($name);
		$this->validateValue($name, $value);
		$this->data[$name] = $value;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 * @author Manuel Will
	 * @since 2013
	 */
	public function __get($name) {
		$this->assertField($name);

		if (!isset($this->data[$name])) {
			return null;
		}

		return $this->data[$name];
	}

	/**
	 * @return array
	 * @author Manuel Will
	 * @since 2013
	 */
	public function getDataRaw() {
		$result = array();
		foreach (array_keys($this->columns) as $keyName) {
			$result[$keyName] = $this->__get($keyName);
		}

		return $result;
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $name
	 * @param string $value
	 */
	private function validateValue($name, &$value) {
		/** @var $meta SAP_Meta */
		$meta = $this->columns[$name];
		$type = $meta->getType();

		switch ($type) {
			case $type === SAP_Meta::INTEGER:
			case $type === SAP_Meta::NUMERIC:
				$value = (int)$value;
				break;

			case $type === SAP_Meta::FLOAT:
				$value = (float)$value;
				break;

			case $type === SAP_Meta::CHAR:
			case $type === SAP_Meta::HEXDECIMAL:
			case $type === SAP_Meta::DATE:
			case $type === SAP_Meta::TIME:
			case $type === SAP_Meta::PACKETNUMBER:
				$value = (string)$value;
				break;
		}
	}

	/**
	 * @param string $fieldName
	 *
	 * @throws Exception
	 * @author Manuel Will
	 * @since 2013
	 */
	private function assertField($fieldName) {
		if (!isset($this->columns[$fieldName])) {
			throw new Exception('Unknown field column "' . $fieldName . '" in ' . get_class($this));
		}
	}

	/**
	 * @author Manuel Will
	 * @since 2013
	 *
	 * @param string $name
	 * @param SAP_Meta $meta
	 */
	public function addColumn($name, SAP_Meta $meta) {
		$this->columns[$name] = $meta;
	}

	/**
	 * @param boolean $isOptional
	 */
	public function setIsOptional($isOptional) {
		$this->isOptional = (bool)$isOptional;
	}

	/**
	 * @return boolean
	 */
	public function getIsOptional() {
		return $this->isOptional;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

}