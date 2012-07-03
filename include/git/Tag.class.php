<?php
/**
 * GitPHP Tag
 *
 * Represents a single tag object
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Ref.class.php');

/**
 * Tag class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tag extends GitPHP_Ref
{
	
	/**
	 * dataRead
	 *
	 * Indicates whether data for this tag has been read
	 *
	 * @access protected
	 */
	protected $dataRead = false;

	/**
	 * object
	 *
	 * Stores the object internally
	 *
	 * @access protected
	 */
	protected $object;

	/**
	 * commitHash
	 *
	 * Stores the commit hash internally
	 *
	 * @access protected
	 */
	protected $commitHash;

	/**
	 * type
	 *
	 * Stores the type internally
	 *
	 * @access protected
	 */
	protected $type;

	/**
	 * tagger
	 *
	 * Stores the tagger internally
	 *
	 * @access protected
	 */
	protected $tagger;

	/**
	 * taggerEpoch
	 *
	 * Stores the tagger epoch internally
	 *
	 * @access protected
	 */
	protected $taggerEpoch;

	/**
	 * taggerTimezone
	 *
	 * Stores the tagger timezone internally
	 *
	 * @access protected
	 */
	protected $taggerTimezone;

	/**
	 * comment
	 *
	 * Stores the tag comment internally
	 *
	 * @access protected
	 */
	protected $comment = array();

	/**
	 * __construct
	 *
	 * Instantiates tag
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $tag tag name
	 * @param string $tagHash tag hash
	 * @return mixed tag object
	 * @throws Exception exception on invalid tag or hash
	 */
	public function __construct($project, $tag, $tagHash = '')
	{
		parent::__construct($project, 'tags', $tag, $tagHash);
	}

	/**
	 * GetObject
	 *
	 * Gets the object this tag points to
	 *
	 * @access public
	 * @return mixed object for this tag
	 */
	public function GetObject()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($this->type == 'commit') {
			return $this->GetProject()->GetCommit($this->object);
		} else if ($this->type == 'tag') {
			return $this->GetProject()->GetTag($this->object);
		} else if ($this->type == 'blob') {
			return $this->GetProject()->GetBlob($this->object);
		}

		return null;
	}

	/**
	 * GetCommit
	 *
	 * Gets the commit this tag points to
	 *
	 * @access public
	 * @return mixed commit for this tag
	 */
	public function GetCommit()
	{
		if ($this->commitHash)
			return $this->GetProject()->GetCommit($this->commitHash);

		if (!$this->dataRead) {
			$this->ReadData();
		}

		if (!$this->commitHash) {
			if ($this->type == 'commit') {
				$this->commitHash = $this->object;
			} else if ($this->type == 'tag') {
				$tag = $this->GetProject()->GetTag($this->object);
				$this->SetCommit($tag->GetCommit());
			}
		}

		return $this->GetProject()->GetCommit($this->commitHash);
	}

	/**
	 * SetCommit
	 *
	 * Sets the commit this tag points to
	 *
	 * @access public
	 * @param mixed $commit commit object 
	 */
	public function SetCommit($commit)
	{
		if (!$commit)
			return;

		$this->SetCommitHash($commit->GetHash());
	}

	/**
	 * SetCommitHash
	 *
	 * Sets the hash of the commit this tag points to
	 *
	 * @access public
	 * @param string $hash hash
	 */
	public function SetCommitHash($hash)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash))
			return;

		if (!$this->commitHash)
			$this->commitHash = $hash;
	}

	/**
	 * GetType
	 *
	 * Gets the tag type
	 *
	 * @access public
	 * @return string tag type
	 */
	public function GetType()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->type;
	}

	/**
	 * GetTagger
	 *
	 * Gets the tagger
	 *
	 * @access public
	 * @return string tagger
	 */
	public function GetTagger()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->tagger;
	}

	/**
	 * GetTaggerEpoch
	 *
	 * Gets the tagger epoch
	 *
	 * @access public
	 * @return string tagger epoch
	 */
	public function GetTaggerEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->taggerEpoch;
	}

	/**
	 * GetTaggerLocalEpoch
	 *
	 * Gets the tagger local epoch
	 *
	 * @access public
	 * @return string tagger local epoch
	 */
	public function GetTaggerLocalEpoch()
	{
		$epoch = $this->GetTaggerEpoch();
		$tz = $this->GetTaggerTimezone();
		if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
			$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
			return $local;
		}
		return $epoch;
	}

	/**
	 * GetTaggerTimezone
	 *
	 * Gets the tagger timezone
	 *
	 * @access public
	 * @return string tagger timezone
	 */
	public function GetTaggerTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->taggerTimezone;
	}

	/**
	 * GetAge
	 *
	 * Gets the tag age
	 *
	 * @access public
	 * @return string age
	 */
	public function GetAge()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return time() - $this->taggerEpoch;
	}

	/**
	 * GetComment
	 *
	 * Gets the tag comment
	 *
	 * @access public
	 * @return array comment lines
	 */
	public function GetComment()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->comment;
	}

	/**
	 * LightTag
	 *
	 * Tests if this is a light tag (tag without tag object)
	 *
	 * @access public
	 * @return boolean true if tag is light (has no object)
	 */
	public function LightTag()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->object)
			return true;

		if (($this->type == 'commit') && ($this->object == $this->GetHash())) {
			return true;
		}

		return false;
	}

	/**
	 * ReadData
	 *
	 * Reads the tag data
	 *
	 * @access protected
	 */
	protected function ReadData()
	{
		$this->dataRead = true;

		if ($this->GetProject()->GetCompat()) {
			$this->ReadDataGit();
		} else {
			$this->ReadDataRaw();
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * ReadDataGit
	 *
	 * Reads the tag data using the git executable
	 *
	 * @access private
	 */
	private function ReadDataGit()
	{
		$args = array();
		$args[] = '-t';
		$args[] = $this->GetHash();
		$ret = trim(GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_CAT_FILE, $args));
		
		if ($ret === 'commit') {
			/* light tag */
			$this->object = $this->GetHash();
			$this->commitHash = $this->GetHash();
			$this->type = 'commit';
			GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
			return;
		}

		/* get data from tag object */
		$args = array();
		$args[] = 'tag';
		$args[] = $this->GetName();
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_CAT_FILE, $args);

		$lines = explode("\n", $ret);

		if (!isset($lines[0]))
			return;

		$objectHash = null;

		$readInitialData = false;
		foreach ($lines as $i => $line) {
			if (!$readInitialData) {
				if (preg_match('/^object ([0-9a-fA-F]{40})$/', $line, $regs)) {
					$objectHash = $regs[1];
					continue;
				} else if (preg_match('/^type (.+)$/', $line, $regs)) {
					$this->type = $regs[1];
					continue;
				} else if (preg_match('/^tag (.+)$/', $line, $regs)) {
					continue;
				} else if (preg_match('/^tagger (.*) ([0-9]+) (.*)$/', $line, $regs)) {
					$this->tagger = $regs[1];
					$this->taggerEpoch = $regs[2];
					$this->taggerTimezone = $regs[3];
					continue;
				}
			}

			$trimmed = trim($line);

			if ((strlen($trimmed) > 0) || ($readInitialData === true)) {
				$this->comment[] = $line;
			}
			$readInitialData = true;

		}

		switch ($this->type) {
			case 'commit':
				$this->object = $objectHash;
				$this->commitHash = $objectHash;
				break;
			case 'tag':
				$args = array();
				$args[] = 'tag';
				$args[] = $objectHash;
				$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_CAT_FILE, $args);
				$lines = explode("\n", $ret);
				foreach ($lines as $i => $line) {
					if (preg_match('/^tag (.+)$/', $line, $regs)) {
						$name = trim($regs[1]);
						$this->object = $name;
					}
				}
				break;
			case 'blob':
				$this->object = $objectHash;
				break;
		}
	}

	/**
	 * ReadDataRaw
	 *
	 * Reads the tag data using the raw git object
	 *
	 * @access private
	 */
	private function ReadDataRaw()
	{
		$data = $this->GetProject()->GetObject($this->GetHash(), $type);
		
		if ($type == GitPHP_Pack::OBJ_COMMIT) {
			/* light tag */
			$this->object = $this->GetHash();
			$this->commitHash = $this->GetHash();
			$this->type = 'commit';
			GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
			return;
		}

		$lines = explode("\n", $data);

		if (!isset($lines[0]))
			return;

		$objectHash = null;

		$readInitialData = false;
		foreach ($lines as $i => $line) {
			if (!$readInitialData) {
				if (preg_match('/^object ([0-9a-fA-F]{40})$/', $line, $regs)) {
					$objectHash = $regs[1];
					continue;
				} else if (preg_match('/^type (.+)$/', $line, $regs)) {
					$this->type = $regs[1];
					continue;
				} else if (preg_match('/^tag (.+)$/', $line, $regs)) {
					continue;
				} else if (preg_match('/^tagger (.*) ([0-9]+) (.*)$/', $line, $regs)) {
					$this->tagger = $regs[1];
					$this->taggerEpoch = $regs[2];
					$this->taggerTimezone = $regs[3];
					continue;
				}
			}

			$trimmed = trim($line);

			if ((strlen($trimmed) > 0) || ($readInitialData === true)) {
				$this->comment[] = $line;
			}
			$readInitialData = true;
		}

		switch ($this->type) {
			case 'commit':
				try {
					$this->object = $objectHash;
					$this->commitHash = $objectHash;
				} catch (Exception $e) {
				}
				break;
			case 'tag':
				$objectData = $this->GetProject()->GetObject($objectHash);
				$lines = explode("\n", $objectData);
				foreach ($lines as $i => $line) {
					if (preg_match('/^tag (.+)$/', $line, $regs)) {
						$name = trim($regs[1]);
						$this->object = $name;
					}
				}
				break;
			case 'blob':
				$this->object = $objectHash;
				break;
		}
	}

	/**
	 * ReadCommit
	 *
	 * Attempts to dereference the commit for this tag
	 *
	 * @access private
	 */
	private function ReadCommit()
	{
		$args = array();
		$args[] = '--tags';
		$args[] = '--dereference';
		$args[] = $this->refName;
		$ret = GitPHP_GitExe::GetInstance()->Execute($this->GetProject()->GetPath(), GIT_SHOW_REF, $args);

		$lines = explode("\n", $ret);

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/tags\/' . preg_quote($this->refName) . '(\^{})$/', $line, $regs)) {
				$this->commitHash = $regs[1];
				return;
			}
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * __sleep
	 *
	 * Called to prepare the object for serialization
	 *
	 * @access public
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('dataRead', 'object', 'commitHash', 'type', 'tagger', 'taggerEpoch', 'taggerTimezone', 'comment');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key to use for this object
	 *
	 * @access public
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		return GitPHP_Tag::CacheKey($this->project, $this->refName);
	}

	/**
	 * GetCreationEpoch
	 *
	 * Gets tag's creation epoch
	 * (tagger epoch, or committer epoch for light tags)
	 *
	 * @access public
	 * @return string creation epoch
	 */
	public function GetCreationEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if ($this->LightTag()) {
			$commit = $this->GetCommit();
			if (!empty($commit))
				return $commit->GetCommitterEpoch();
		}

		return $this->taggerEpoch;
	}

	/**
	 * CompareAge
	 *
	 * Compares two tags by age
	 *
	 * @access public
	 * @static
	 * @param mixed $a first tag
	 * @param mixed $b second tag
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$aObj = $a->GetObject();
		$bObj = $b->GetObject();
		if (($aObj instanceof GitPHP_Commit) && ($bObj instanceof GitPHP_Commit)) {
			return GitPHP_Commit::CompareAge($aObj, $bObj);
		}

		if ($aObj instanceof GitPHP_Commit)
			return 1;

		if ($bObj instanceof GitPHP_Commit)
			return -1;

		return strcmp($a->GetName(), $b->GetName());
	}

	/**
	 * CompareCreationEpoch
	 *
	 * Compares to tags by creation epoch
	 *
	 * @access public
	 * @static
	 * @param mixed $a first tag
	 * @param mixed $b second tag
	 * @return integer comparison result
	 */
	public static function CompareCreationEpoch($a, $b)
	{
		$aEpoch = $a->GetCreationEpoch();
		$bEpoch = $b->GetCreationEpoch();

		if ($aEpoch == $bEpoch) {
			return 0;
		}

		return ($aEpoch < $bEpoch ? 1 : -1);
	}

	/**
	 * CacheKey
	 *
	 * Generates a tag cache key
	 *
	 * @access public
	 * @static
	 * @param string $proj project
	 * @param string $tag tag name
	 * @return string cache key
	 */
	public static function CacheKey($proj, $tag)
	{
		return 'project|' . $proj . '|tag|' . $tag;
	}

}
