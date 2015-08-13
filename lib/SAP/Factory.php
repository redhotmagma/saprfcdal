<?php

/**
 * SAP_Factory
 *
 * @author Manuel Will <insphare@gmail.com>
 * @since  2015-08
 */
class SAP_Factory {

	/**
	 * @var array
	 */
	private $credentials = [];

	/**
	 * @var array
	 */
	private $compileDirectory = '';

	/**
	 * @var self
	 */
	private static $singleton = null;

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 * @since  2015-08
	 * @return SAP_Factory
	 * @throws SAP_Exception
	 */
	public static function get() {
		if (null === self::$singleton) {
			throw new SAP_Exception('SapRfcDal is not initialized.');
		}

		return self::$singleton;
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 * @since  2015-08
	 *
	 * @param array $credentials
	 *
	 * @throws SAP_Exception
	 */
	public static function init(array $credentials, $compileDirectory) {
		if (null !== self::$singleton) {
			throw new SAP_Exception('SapRfcDal is already initialized.');
		}
		self::$singleton = new self($credentials, $compileDirectory);
	}

	/**
	 * @param array $credentials
	 */
	public function __construct(array $credentials, $compileDirectory) {
		$this->credentials = $credentials;
		$this->compileDirectory = (string)$compileDirectory;

		spl_autoload_register([
			$this,
			'autoloadCompiledClass'
		]);
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 * @since  2015-08
	 * @return array
	 */
	public function getCredentials() {
		return $this->credentials;
	}

	/**
	 * @return array
	 */
	public function getCompileDirectory() {
		return $this->compileDirectory;
	}

	/**
	 * @author Manuel Will <will@redhotmagma.de>
	 * @since  2015-08
	 *
	 * @param string $className
	 *
	 * @return bool
	 */
	protected function autoloadCompiledClass($className) {
		if (!preg_match('~^SAP_~', $className)) {
			return false;
		}

		$classToPath = str_replace('_', DIRECTORY_SEPARATOR, $className);
		$classToPath = $this->getCompileDirectory() . $classToPath . '.php';
		if (file_exists($classToPath)) {
			include_once($classToPath);

			return true;
		}

		return false;
	}

}