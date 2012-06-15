<?php
/**
 * GitPHP ProjectListBase
 *
 * Base class that all projectlist classes extend
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

define('GITPHP_SORT_PROJECT', 'project');
define('GITPHP_SORT_DESCRIPTION', 'descr');
define('GITPHP_SORT_OWNER', 'owner');
define('GITPHP_SORT_AGE', 'age');

/**
 * ProjectListBase class
 *
 * @package GitPHP
 * @subpackage Git
 * @abstract
 */
abstract class GitPHP_ProjectListBase implements Iterator
{
	/**
	 * projects
	 *
	 * Stores array of projects internally
	 *
	 * @access protected
	 */
	protected $projects;

	/**
	 * projectsLoaded
	 *
	 * Stores whether the list of projects has been loaded
	 *
	 * @access protected
	 */
	protected $projectsLoaded = false;

	/**
	 * projectConfig
	 *
	 * Stores the project configuration internally
	 *
	 * @access protected
	 */
	protected $projectConfig = null;

	/**
	 * projectSettings
	 *
	 * Stores the project settings internally
	 *
	 * @access protected
	 */
	protected $projectSettings = null;

	/**
	 * projectRoot
	 *
	 * Stores the project root internally
	 *
	 * @access protected
	 */
	protected $projectRoot = null;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->projects = array();
		$this->projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));
		if (empty($this->projectRoot)) {
			throw new GitPHP_MessageException(__('A projectroot must be set in the config'), true, 500);
		}
		if (!is_dir($this->projectRoot)) {
			throw new Exception(sprintf(__('%1$s is not a directory'), $projectDir));
		}

	}

	/**
	 * IsDir
	 *
	 * @access protected
	 * @return boolean true if folder or a link pointing to a folder
	 * @param string path to check
	 */
	protected function IsDir($dir)
	{
		return is_dir($dir) || (is_link($dir) && is_dir("$dir/."));
	}

	/**
	 * HasProject
	 *
	 * Test if the projectlist contains
	 * the given project
	 *
	 * @access public
	 * @return boolean true if project exists in list
	 * @param string $project the project string to find
	 */
	public function HasProject($project)
	{
		if (empty($project))
			return false;

		return isset($this->projects[$project]);
	}

	/**
	 * GetProject
	 *
	 * Gets a particular project
	 *
	 * @access public
	 * @return mixed project object or null
	 * @param string $project the project to find
	 */
	public function GetProject($project)
	{
		if (empty($project))
			return null;

		if (isset($this->projects[$project]))
			return $this->projects[$project];

		if (!$this->projectsLoaded) {
			$projObj = $this->InstantiateProject($project);
			$this->projects[$project] = $projObj;
			return $projObj;
		}

		return null;
	}

	/**
	 * InstantiateProject
	 *
	 * Instantiates a project object
	 *
	 * @access protected
	 * @param string $proj project
	 * @return mixed project object
	 */
	protected function InstantiateProject($proj)
	{
		$project = new GitPHP_Project(GitPHP_Util::AddSlash($this->projectRoot), $proj);

		if ($this->projectSettings && isset($this->projectSettings[$proj])) {
			$this->ApplyProjectSettings($project, $this->projectSettings[$proj]);
		}

		return $project;
	}

	/**
	 * GetConfig
	 *
	 * Gets the config defined for this ProjectList
	 *
	 * @access public
	 */
	public function GetConfig()
	{
		return $this->projectConfig;
	}

	/**
	 * GetSettings
	 *
	 * Gets the settings applied to this projectlist
	 *
	 * @access public
	 */
	public function GetSettings()
	{
		return $this->projectSettings;
	}

	/**
	 * LoadProjects
	 *
	 * Loads all projects in the list
	 *
	 * @access public
	 */
	public function LoadProjects()
	{
		$this->PopulateProjects();

		$this->projectsLoaded = true;

		$this->Sort();

		$this->ApplySettings();
	}

	/**
	 * PopulateProjects
	 *
	 * Populates the internal list of projects
	 *
	 * @access protected
	 */
	abstract protected function PopulateProjects();

	/**
	 * rewind
	 *
	 * Rewinds the iterator
	 */
	function rewind()
	{
		return reset($this->projects);
	}

	/**
	 * current
	 *
	 * Returns the current element in the array
	 */
	function current()
	{
		return current($this->projects);
	}

	/**
	 * key
	 *
	 * Returns the current key
	 */
	function key()
	{
		return key($this->projects);
	}

	/**
	 * next
	 * 
	 * Advance the pointer
	 */
	function next()
	{
		return next($this->projects);
	}

	/**
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		return key($this->projects) !== null;
	}

	/**
	 * Sort
	 *
	 * Sorts the project list
	 *
	 * @access public
	 * @param string $sortBy sort method
	 */
	public function Sort($sortBy = GITPHP_SORT_PROJECT)
	{
		switch ($sortBy) {
			case GITPHP_SORT_DESCRIPTION:
				uasort($this->projects, array('GitPHP_Project', 'CompareDescription'));
				break;
			case GITPHP_SORT_OWNER:
				uasort($this->projects, array('GitPHP_Project', 'CompareOwner'));
				break;
			case GITPHP_SORT_AGE:
				$this->GetCategoryAges();
				uasort($this->projects, array('GitPHP_Project', 'CompareAge'));
				break;
			case GITPHP_SORT_PROJECT:
			default:
				uasort($this->projects, array('GitPHP_Project', 'CompareProject'));
				break;
		}
	}

	/**
	 * Count
	 *
	 * Gets the count of projects
	 *
	 * @access public
	 * @return integer number of projects
	 */
	public function Count()
	{
		return count($this->projects);
	}

	/**
	 * Filter
	 *
	 * Returns a filtered list of projects
	 *
	 * @access public
	 * @param string $filter filter pattern
	 * @return array array of filtered projects
	 */
	public function Filter($pattern = null)
	{
		if (empty($pattern))
			return $this->projects;

		$matches = array();

		foreach ($this->projects as $proj) {
			if ((stripos($proj->GetProject(), $pattern) !== false) ||
			    (stripos($proj->GetDescription(), $pattern) !== false) ||
			    (stripos($proj->GetOwner(), $pattern) !== false)) {
			    	$matches[] = $proj;
			}
		}

		return $matches;
	}

	/**
	 * GetCategoryAges
	 *
	 * Store project category age
	 *
	 * @access protected
	 */
	protected function GetCategoryAges()
	{
		foreach ($this->projects as $proj) {
			$cat = $proj->GetCategory('none');
			if (isset($ages[$cat]))
				$ages[$cat] = min($ages[$cat], $proj->GetAge());
			else
				$ages[$cat] = $proj->GetAge();
		}
		foreach ($this->projects as $proj) {
			$cat = $proj->GetCategory('none');
			$proj->categoryAge = $ages[$cat];
		}
	}

	/**
	 * ApplyProjectSettings
	 *
	 * Applies override settings for a project
	 *
	 * @access protected
	 * @param string $project the project object
	 * @param array $projData project data array
	 */
	protected function ApplyProjectSettings($project, $projData)
	{
		if (!$project)
			return;

		if (isset($projData['category']) && is_string($projData['category'])) {
			$project->SetCategory($projData['category']);
		}
		if (isset($projData['owner']) && is_string($projData['owner'])) {
			$project->SetOwner($projData['owner']);
		}
		if (isset($projData['description']) && is_string($projData['description'])) {
			$project->SetDescription($projData['description']);
		}
		if (isset($projData['cloneurl']) && is_string($projData['cloneurl'])) {
			$project->SetCloneUrl($projData['cloneurl']);
		}
		if (isset($projData['pushurl']) && is_string($projData['pushurl'])) {
			$project->SetPushUrl($projData['pushurl']);
		}
		if (isset($projData['bugpattern']) && is_string($projData['bugpattern'])) {
			$project->SetBugPattern($projData['bugpattern']);
		}
		if (isset($projData['bugurl']) && is_string($projData['bugurl'])) {
			$project->SetBugUrl($projData['bugurl']);
		}
		if (isset($projData['compat'])) {
			$project->SetCompat($projData['compat']);
		}
		if (isset($projData['website']) && is_string($projData['website'])) {
			$project->SetWebsite($projData['website']);
		}
	}

	/**
	 * SetSettings
	 *
	 * Sets a list of settings for the project list
	 *
	 * @access protected
	 * @param array $settings the array of settings
	 */
	public function SetSettings($settings)
	{
		if ((!$settings) || (count($settings) < 1))
			return;

		$this->projectSettings = $settings;

		$this->ApplySettings();
	}

	/**
	 * ApplySettings
	 *
	 * Applies project settings to project list
	 *
	 * @access protected
	 */
	protected function ApplySettings()
	{
		if (!$this->projectSettings)
			return;

		if (count($this->projects) > 0) {
			foreach ($this->projectSettings as $proj => $setting) {

				if (empty($proj)) {
					if (isset($setting['project']) && !empty($setting['project'])) {
						$proj = $setting['project'];
					}
				}

				if (!isset($this->projects[$proj]))
					continue;

				$this->ApplyProjectSettings($this->projects[$proj], $setting);
			}
		}
	}
}
