{*
 * Shortlog List
 *
 * Shortlog list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table class="shortlog">
   {assign var="baseurl"
         value="{$SCRIPT_NAME}?p={$project->GetProject('f')}"
   }
   {foreach from=$revlist item=rev}

     <tr class="{cycle values="light,dark"} {$rev->glyphClass}" title="{foreach from=$rev->GetParents() item=par}{$par->GetHash(true)} {/foreach}">
       <td class="glyph hidden">{$rev->glyph}</td>
       <td class="hash monospace">{$rev->GetHash(true)}</td>
       <td title="{if $rev->GetAge() > 60*60*24*7*2}{agestring age=$rev->GetAge()}{else}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em>{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{agestring age=$rev->GetAge()}{/if}</em></td>
       <td><em>{$rev->GetAuthorName()}</em></td>
       <td>
         <a href="{$baseurl}&amp;a=commit&amp;h={$rev->GetHash()}" class="list commitTip" {if strlen($rev->GetTitle()) > 80}title="{$rev->GetTitle()|escape}"{/if}>
         {if $rev->IsMergeCommit()}<span class="merge_title">{else}<span class="commit_title">{/if}{$rev->GetTitle(80)|escape}</span>
         </a>
	 {include file='refbadges.tpl' commit=$rev}
       </td>
       <td class="link">
         {assign var=revtree value=$rev->GetTree()}
         <a href="{$baseurl}&amp;a=commit&amp;h={$rev->GetHash()}">{t}commit{/t}</a> | <a href="{$baseurl}&amp;a=commitdiff&amp;h={$rev->GetHash()}">{t}commitdiff{/t}</a> | <a href="{$baseurl}&amp;a=tree&amp;h={$revtree->GetHash()}&amp;hb={$rev->GetHash()}">{t}tree{/t}</a> | <a href="{$baseurl}&amp;a=snapshot&amp;h={$rev->GetHash()}" class="snapshotTip">{t}snapshot{/t}</a>
	 {if $source == 'shortlog'}
	  | 
	  {if $mark}
	    {if $mark->GetHash() == $rev->GetHash()}
	      <a href="{$baseurl}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}deselect{/t}</a>
	    {else}
	      {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
	        {assign var=markbase value=$mark}
		{assign var=markparent value=$rev}
	      {else}
	        {assign var=markbase value=$rev}
		{assign var=markparent value=$mark}
	      {/if}
	      <a href="{$baseurl}&amp;a=commitdiff&amp;h={$markbase->GetHash()}&amp;hp={$markparent->GetHash()}">{t}diff with selected{/t}</a>
	    {/if}
	  {else}
	    <a href="{$baseurl}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page}&amp;m={$rev->GetHash()}">{t}select for diff{/t}</a>
	  {/if}
	{/if}
       </td>
     </tr>
   {foreachelse}
     <tr><td><em>{t}No commits{/t}</em></td></tr>
   {/foreach}

   {if $hasmorerevs && $commit}
     <tr>
     {if $source == 'summary'}
       <td><a href="{$baseurl}&amp;a=shortlog">&hellip;</a></td>
       <td></td><td></td><td></td><td></td>
     {else if $source == 'shortlog'}
       <td><a href="{$baseurl}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" title="Alt-n">{t}next{/t}</a></td>
       <td></td><td></td><td></td><td></td>
     {/if}
     </tr>
   {/if}
 </table>

