<?php
/**
 * Controller for displaying heads
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Remotes extends GitPHP_ControllerBase
{

	/**
	 * GetTemplate
	 *
	 * Gets the template for this controller
	 *
	 * @access protected
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'remotes.tpl';
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return '';
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @access public
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local && $this->resource) {
			return $this->resource->translate('remotes');
		}
		return 'remotes';
	}

	/**
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @access protected
	 */
	protected function ReadQuery()
	{
	}

	/**
	 * LoadData
	 *
	 * Loads data for this template
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		$headlist = $this->GetProject()->GetRemotes();
		if (isset($headlist) && (count($headlist) > 0)) {
			$this->tpl->assign("remotelist",$headlist);
		}

		$head = $this->GetProject()->GetHeadCommit();
		$this->tpl->assign("head",$head);
	}

}