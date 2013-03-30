<?php
/**
 * Represents an archive (snapshot)
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

class GitPHP_Archive
{
	/**
	 * Compression formats
	 */
	const COMPRESS_TAR = 'tar';
	const COMPRESS_BZ2 = 'bz2';
	const COMPRESS_GZ  = 'gz';
	const COMPRESS_ZIP = 'zip';

	/**
	 * objectType
	 *
	 * Stores the object type for this archive
	 *
	 * @access protected
	 */
	protected $objectType;

	/**
	 * objectHash
	 *
	 * Stores the object hash for this archive
	 *
	 * @access protected
	 */
	protected $objectHash;

	/**
	 * project
	 *
	 * Stores the project for this archive internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * format
	 *
	 * Stores the archive format internally
	 *
	 * @access protected
	 */
	protected $format;

	/**
	 * fileName
	 *
	 * Stores the archive filename internally
	 *
	 * @access protected
	 */
	protected $fileName = '';

	/**
	 * path
	 *
	 * Stores the archive path internally
	 *
	 * @access protected
	 */
	protected $path = '';

	/**
	 * prefix
	 *
	 * Stores the archive prefix internally
	 *
	 * @access protected
	 */
	protected $prefix = '';

	/**
	 * handle
	 *
	 * Stores the process handle
	 *
	 * @access protected
	 */
	protected $handle = false;

	/**
	 * tempfile
	 *
	 * Stores the temp file name
	 *
	 * @access protected
	 */
	protected $tempfile = '';

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $gitObject the object
	 * @param integer $format the format for the archive
	 * @return mixed git archive
	 */
	public function __construct($project, $gitObject, $format = GITPHP_FORMAT_ZIP, $path = '', $prefix = '')
	{
		$this->SetProject($project);
		$this->SetObject($gitObject);
		if (!$this->project && $gitObject) {
			$this->project = $gitObject->GetProject()->GetProject();
		}
		$this->SetFormat($format);
		$this->SetPath($path);
		$this->SetPrefix($prefix);
	}

	/**
	 * GetFormat
	 *
	 * Gets the archive format
	 *
	 * @access public
	 * @return integer archive format
	 */
	public function GetFormat()
	{
		return $this->format;
	}

	/**
	 * SetFormat
	 *
	 * Sets the archive format
	 *
	 * @access public
	 * @param integer $format archive format
	 */
	public function SetFormat($format)
	{
		if ((($format == self::COMPRESS_BZ2) && (!function_exists('bzcompress'))) ||
		    (($format == self::COMPRESS_GZ) && (!function_exists('gzencode')))) {
		    /*
		     * Trying to set a format but doesn't have the appropriate
		     * compression function, fall back to tar
		     */
		    $format = self::COMPRESS_TAR;
		}

		$this->format = $format;
	}

	/**
	 * GetObject
	 *
	 * Gets the object for this archive
	 *
	 * @access public
	 * @return mixed the git object
	 */
	public function GetObject()
	{
		if ($this->objectType == 'commit') {
			return $this->GetProject()->GetCommit($this->objectHash);
		}

		if ($this->objectType = 'tree') {
			return $this->GetProject()->GetTree($this->objectHash);
		}

		return null;
	}

	/**
	 * SetObject
	 *
	 * Sets the object for this archive
	 *
	 * @access public
	 * @param mixed $object the git object
	 */
	public function SetObject($object)
	{
		// Archive only works for commits and trees

		if ($object == null) {
			$this->objectHash = '';
			$this->objectType = '';
			return;
		}

		if ($object instanceof GitPHP_Commit) {
			$this->objectType = 'commit';
			$this->objectHash = $object->GetHash();
			return;
		}

		if ($object instanceof GitPHP_Tree) {
			$this->objectType = 'tree';
			$this->objectHash = $object->GetHash();
			return;
		}

		throw new Exception('Invalid source object for archive');
	}

	/**
	 * GetProject
	 *
	 * Gets the project for this archive
	 *
	 * @access public
	 * @return mixed the project
	 */
	public function GetProject()
	{
		if ($this->project)
			return GitPHP_ProjectList::GetInstance()->GetProject($this->project);

		return null;
	}

	/**
	 * SetProject
	 *
	 * Sets the project for this archive
	 *
	 * @access public
	 * @param mixed $project the project
	 */
	public function SetProject($project)
	{
		if ($project)
			$this->project = $project->GetProject();
		else
			$this->project = null;
	}

	/**
	 * GetExtension
	 *
	 * Gets the extension to use for this archive
	 *
	 * @access public
	 * @return string extension for the archive
	 */
	public function GetExtension()
	{
		return GitPHP_Archive::FormatToExtension($this->format);
	}

	/**
	 * GetFilename
	 *
	 * Gets the filename for this archive
	 *
	 * @access public
	 * @return string filename
	 */
	public function GetFilename()
	{
		if (!empty($this->fileName)) {
			return $this->fileName;
		}

		$fname = $this->GetProject()->GetSlug();

		if (!empty($this->path)) {
			$fname .= '-' . GitPHP_Util::MakeSlug($this->path);
		}

		if (!empty($this->objectHash)) {
			$fname .= '-' . $this->GetProject()->AbbreviateHash($this->objectHash);
		}

		$fname .= '.' . $this->GetExtension();

		return $fname;
	}

	/**
	 * SetFilename
	 *
	 * Sets the filename for this archive
	 *
	 * @access public
	 * @param string $name filename
	 */
	public function SetFilename($name = '')
	{
		$this->fileName = $name;
	}

	/**
	 * GetPath
	 *
	 * Gets the path to restrict this archive to
	 *
	 * @access public
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * SetPath
	 *
	 * Sets the path to restrict this archive to
	 *
	 * @access public
	 * @param string $path path to restrict
	 */
	public function SetPath($path = '')
	{
		$this->path = $path;
	}

	/**
	 * GetPrefix
	 *
	 * Gets the directory prefix to use for files in this archive
	 *
	 * @access public
	 * @return string prefix
	 */
	public function GetPrefix()
	{
		if (!empty($this->prefix)) {
			return $this->prefix;
		}

		$pfx = $this->GetProject()->GetSlug() . '/';

		if (!empty($this->path))
			$pfx .= $this->path . '/';

		return $pfx;
	}

	/**
	 * SetPrefix
	 *
	 * Sets the directory prefix to use for files in this archive
	 *
	 * @access public
	 * @param string $prefix prefix to use
	 */
	public function SetPrefix($prefix = '')
	{
		if (empty($prefix)) {
			$this->prefix = $prefix;
			return;
		}

		if (substr($prefix, -1) != '/') {
			$prefix .= '/';
		}

		$this->prefix = $prefix;
	}

	/**
	 * Open
	 *
	 * Opens a descriptor for reading archive data
	 *
	 * @access public
	 * @return boolean true on success
	 */
	public function Open()
	{
		if (!$this->objectHash)
		{
			throw new Exception('Invalid object for archive');
		}

		if ($this->handle) {
			return true;
		}

		$args = array();

		switch ($this->format) {
			case self::COMPRESS_ZIP:
				$args[] = '--format=zip';
				break;
			case self::COMPRESS_TAR:
			case self::COMPRESS_BZ2:
			case self::COMPRESS_GZ:
				$args[] = '--format=tar';
				break;
		}

		$args[] = '--prefix=' . $this->GetPrefix();
		$args[] = $this->objectHash;

		$this->handle = GitPHP_GitExe::GetInstance()->Open($this->GetProject()->GetPath(), GIT_ARCHIVE, $args);

		if ($this->format == self::COMPRESS_GZ) {
			// hack to get around the fact that gzip files
			// can't be compressed on the fly and the php zlib stream
			// doesn't seem to daisy chain with any non-file streams

			$this->tempfile = tempnam(sys_get_temp_dir(), "GitPHP");

			$compress = GitPHP_Config::GetInstance()->GetValue('compresslevel');

			$mode = 'wb';
			if (is_int($compress) && ($compress >= 1) && ($compress <= 9))
				$mode .= $compress;

			$temphandle = gzopen($this->tempfile, $mode);
			if ($temphandle) {
				while (!feof($this->handle)) {
					gzwrite($temphandle, fread($this->handle, 1048576));
				}
				gzclose($temphandle);

				$temphandle = fopen($this->tempfile, 'rb');
			}
			
			if ($this->handle) {
				pclose($this->handle);
			}
			$this->handle = $temphandle;
		}

		return ($this->handle !== false);
	}

	/**
	 * Close
	 *
	 * Close the archive data descriptor
	 *
	 * @access public
	 * @return boolean true on success
	 */
	public function Close()
	{
		if (!$this->handle) {
			return true;
		}

		if ($this->format == self::COMPRESS_GZ) {
			fclose($this->handle);
			if (!empty($this->tempfile)) {
				unlink($this->tempfile);
				$this->tempfile = '';
			}
		} else {
			pclose($this->handle);
		}

		$this->handle = null;
		
		return true;
	}

	/**
	 * Read
	 *
	 * Read a chunk of the archive data
	 *
	 * @access public
	 * @param int $size size of data to read
	 * @return string archive data
	 */
	public function Read($size = 1048576)
	{
		if (!$this->handle) {
			return false;
		}

		if (feof($this->handle)) {
			return false;
		}

		$data = fread($this->handle, $size);

		if ($this->format == self::COMPRESS_BZ2) {
			$data = bzcompress($data, GitPHP_Config::GetInstance()->GetValue('compresslevel', 4));
		}

		return $data;
	}

	/**
	 * FormatToExtension
	 *
	 * Gets the extension to use for a particular format
	 *
	 * @access public
	 * @static
	 * @param string $format format to get extension for
	 * @return string file extension
	 */
	public static function FormatToExtension($format)
	{
		switch ($format) {
			case self::COMPRESS_TAR:
				return 'tar';
				break;
			case self::COMPRESS_BZ2:
				return 'tar.bz2';
				break;
			case self::COMPRESS_GZ:
				return 'tar.gz';
				break;
			case self::COMPRESS_ZIP:
				return 'zip';
				break;
		}
	}

	/**
	 * SupportedFormats
	 *
	 * Gets the supported formats for the archiver
	 *
	 * @access public
	 * @static
	 * @return array array of formats mapped to extensions
	 */
	public static function SupportedFormats()
	{
		$formats = array();

		$formats[self::COMPRESS_TAR] = GitPHP_Archive::FormatToExtension(self::COMPRESS_TAR);
		
		// TODO check for git > 1.4.3 for zip
		$formats[self::COMPRESS_ZIP] = GitPHP_Archive::FormatToExtension(self::COMPRESS_ZIP);

		if (function_exists('bzcompress'))
			$formats[self::COMPRESS_BZ2] = GitPHP_Archive::FormatToExtension(self::COMPRESS_BZ2);

		if (function_exists('gzencode'))
			$formats[self::COMPRESS_GZ] = GitPHP_Archive::FormatToExtension(self::COMPRESS_GZ);

		return $formats;
	}

}
