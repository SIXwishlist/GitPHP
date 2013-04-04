<?php
/**
 * Base class for all git objects that represent a filesystem item
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_FilesystemObject extends GitPHP_GitObject
{
	/**
	 * File type constants
	 */
	const UnknownType = 0;
	const DirectoryType = 1;
	const SymlinkType = 2;
	const FileType = 3;

	/**
	 * Standard style constants
	 */
	const TYPE_UNKNOWN = 0;
	const TYPE_FOLDER = 1;
	const TYPE_LINK = 2;
	const TYPE_FILE = 3;

	/**
	 * Object path
	 */
	protected $path = '';

	/**
	 * Object mode
	 */
	protected $mode;

	/**
	 * Hash of the commit this object belongs to
	 */
	protected $commitHash;

	/**
	 * The trees of this object's base path
	 * @var array
	 */
	protected $pathTree;

	/**
	 * Whether the trees of the object's base path have been read
	 */
	protected $pathTreeRead = false;

	/**
	 * Instantiates object
	 *
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * Gets the object name
	 *
	 * @return string name
	 */
	public function GetName()
	{
		if (!empty($this->path))
			return GitPHP_Util::BaseName($this->path);

		return '';
	}

	/**
	 * Gets the full path
	 *
	 * @return string path
	 */
	public function GetPath()
	{
		if (!empty($this->path))
			return $this->path;

		return '';
	}

	/**
	 * Sets the object path
	 *
	 * @param string $path object path
	 */
	public function SetPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Gets the object mode
	 *
	 * @return string mode
	 */
	public function GetMode()
	{
		return $this->mode;
	}

	/**
	 * Gets the mode as a readable string
	 *
	 * @return string mode string
	 */
	public function GetModeString()
	{
		if (empty($this->mode))
			return '';

		$mode = octdec($this->mode);

		/*
		 * Git doesn't store full ugo modes,
		 * it only knows if something is a directory,
		 * symlink, or an executable or non-executable file
		 */
		if (($mode & 0x4000) == 0x4000)
			return 'drwxr-xr-x';
		else if (($mode & 0xA000) == 0xA000)
			return 'lrwxrwxrwx';
		else if (($mode & 0x8000) == 0x8000) {
			if (($mode & 0x0040) == 0x0040)
				return '-rwxr-xr-x';
			else
				return '-rw-r--r--';
		}
		return '----------';
	}

	/**
	 * Sets the object mode
	 *
	 * @param string $mode tree mode
	 */
	public function SetMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * Gets the commit this object belongs to
	 *
	 * @return mixed commit object
	 */
	public function GetCommit()
	{
		return $this->GetProject()->GetCommit($this->commitHash);
	}

	/**
	 * Sets the commit this object belongs to
	 *
	 * @param mixed $commit commit object
	 */
	public function SetCommit($commit)
	{
		if (!$commit)
			return;

		$this->SetCommitHash($commit->GetHash());
	}

	/**
	 * Sets the hash of the commit this object belongs to
	 *
	 * @param string $commitHash commit hash
	 */
	public function SetCommitHash($commitHash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $commitHash))
			return;

		$this->commitHash = $commitHash;
	}

	/**
	 * Gets the objects of the base path
	 *
	 * @return GitPHP_Tree[] array of tree objects
	 */
	public function GetPathTree()
	{
		if (!$this->pathTreeRead)
			$this->ReadPathTree();

		$pathTree = array();
		$usedTrees = array();
		for ($i = 0; $i < count($this->pathTree); ++$i) {
			$data = $this->pathTree[$i];

			if (isset($data['hash']) && !empty($data['hash'])) {
				$tree = $this->GetProject()->GetObjectManager()->GetTree($data['hash']);
				if (isset($usedTrees[$data['hash']])) {
					$tree = clone $obj;
				} else {
					$usedTrees[$data['hash']] = 1;
				}
				$tree->SetPath($data['path']);
				$pathTree[] = $tree;
			}
		}

		return $pathTree;
	}

	/**
	 * Reads the objects of the base path
	 */
	private function ReadPathTree()
	{
		$this->pathTreeRead = true;

		if (empty($this->path)) {
			/* this is a top level tree, it has no subpath */
			return;
		}

		$path = $this->path;

		$tree = $this->GetCommit()->GetTree();

		while (($pos = strrpos($path, '/')) !== false) {
			$path = substr($path, 0, $pos);
			$pathhash = $tree->PathToHash($path);
			if (!empty($pathhash)) {
				$data = array();
				$data['hash'] = $pathhash;
				$data['path'] = $path;
				$this->pathTree[] = $data;
			}
		}

		if (count($this->pathTree) > 0) {
			$this->pathTree = array_reverse($this->pathTree);
		}
	}

	/**
	 * Gets a filesystem object type from its octal mode
	 *
	 * @param string $octMode octal mode
	 * @return int file type
	 */
	public static function ObjectType($octMode)
	{
		$mode = octdec($octMode);

		if (($mode & 0x4000) == 0x4000) {
			return self::TYPE_FOLDER;
		} else if (($mode & 0xA000) == 0xA000) {
			return self::TYPE_LINK;
		} else if (($mode & 0x8000) == 0x8000) {
			return self::TYPE_FILE;
		}

		return self::TYPE_UNKNOWN;
	}

	/**
	 * Compares two objects by path
	 *
	 * @param GitPHP_FilesystemObject $a first object
	 * @param GitPHP_FilesystemObject $b second object
	 * @return integer comparison result
	 */
	public static function ComparePath($a, $b)
	{
		return strcmp($a->GetPath(), $b->GetPath());
	}

}
