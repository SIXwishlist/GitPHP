<?php
/**
 * Constant for tar archive
 */
define('GITPHP_COMPRESS_TAR', 'tar');

/**
 * Constant for bz2 archive
 */
define('GITPHP_COMPRESS_BZ2', 'tbz2');

/**
 * Constant for gz archive
 */
define('GITPHP_COMPRESS_GZ', 'tgz');

/**
 * Constant for zip archive
 */
define('GITPHP_COMPRESS_ZIP', 'zip');

/**
 * Configfile reader class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */
class GitPHP_Config
{
	
	/**
	 * Stores the singleton instance
	 *
	 * @var GitPHP_Config
	 */
	protected static $instance;

	/**
	 * Stores the config values
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * Stores the config files
	 *
	 * @var string[]
	 */
	protected $configs = array();

	/**
	 * Returns the singleton instance
	 *
	 * @return GitPHP_Config instance of config class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_Config();
		}
		return self::$instance;
	}

	/**
	 * Releases the singleton instance
	 */
	public static function DestroyInstance()
	{
		self::$instance = null;
	}

	/**
	 * Class constructor
	 */
	private function __construct()
	{
	}

	/**
	 * Loads a config file
	 *
	 * @param string $configFile config file to load
	 * @throws Exception on failure
	 */
	public function LoadConfig($configFile)
	{
		// backwards compatibility for people who have been
		// making use of these variables in their title
		global $gitphp_version, $gitphp_appstring;

		if (!is_file($configFile)) {
			throw new GitPHP_MessageException('Could not load config file ' . $configFile, true, 500);
		}

		if (!include($configFile)) {
			throw new GitPHP_MessageException('Could not read config file ' . $configFile, true, 500);
		}

		if (isset($gitphp_conf) && is_array($gitphp_conf))
			$this->values = array_merge($this->values, $gitphp_conf);

		$this->configs[] = $configFile;
	}

	/**
	 * Clears all config values
	 */
	public function ClearConfig()
	{
		$this->values = array();
		$this->configs = array();
	}

	/**
	 * Gets a config value
	 *
	 * @param string $key config key to fetch
	 * @param mixed $default default config value to return
	 * @return mixed config value
	 */
	public function GetValue($key, $default = null)
	{
		if ($this->HasKey($key)) {
			return $this->values[$key];
		}
		return $default;
	}

	/**
	 * Sets a config value
	 *
	 * @param string $key config key to set
	 * @param mixed $value value to set
	 */
	public function SetValue($key, $value)
	{
		if (empty($key)) {
			return;
		}
		if (empty($value)) {
			unset($this->values[$key]);
			return;
		}
		$this->values[$key] = $value;
	}

	/**
	 * Tests if the config has specified this key
	 *
	 * @param string $key config key to find
	 * @return boolean true if key exists
	 */
	public function HasKey($key)
	{
		if (empty($key)) {
			return false;
		}
		return isset($this->values[$key]);
	}

}
