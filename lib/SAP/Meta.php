<?php

/**
 * Class SAP_Meta
 *
 * @author Manuel Will
 * @since 2013
 */
class SAP_Meta {

	/**
	 *
	 */
	const CHAR = 'CHAR';

	/**
	 *
	 */
	const DATE = 'DATE'; // YYYYMMDD
	/**
	 *
	 */
	const NUMERIC = 'NUMERIC';

	/**
	 *
	 */
	const PACKETNUMBER = 'PACKETNUMBER';

	/**
	 *
	 */
	const INTEGER = 'INTEGER';

	/**
	 *
	 */
	const FLOAT = 'FLOAT';

	/**
	 *
	 */
	const TIME = 'TIME'; // HHMMSS
	/**
	 *
	 */
	const HEXDECIMAL = 'HEXDECIMAL'; // Hexadecimal
	/**
	 *
	 */
	const NAMELESS = 'nameless';

	/**
	 * Data type.
	 *
	 * @var string
	 */
	private $type = '';

	/**
	 * Data type length.
	 *
	 * @var null
	 */
	private $length = null;

	/**
	 * Flag for primary key definition.
	 *
	 * @var bool
	 */
	private $isOptional = false;

	/**
	 * @param null $length
	 */
	public function setLength($length) {
		$this->length = (int)$length;
	}

	/**
	 * @return null
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
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

}