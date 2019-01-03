<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Comment\Service\Variables;

use EllisLab\ExpressionEngine\Model\Comment\Comment as CommentModel;
use EllisLab\ExpressionEngine\Service\Template\Variables;

/**
 * Comment Variables
 */
class Comment extends Variables {

	/**
	 * @var object namespace EllisLab\ExpressionEngine\Model\Member\Member
	 */
	private $author;

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Channel\Channel
	 */
	private $channel;

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Comment\Comment
	 */
	private $comment;

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Channel\ChannelEntry
	 */
	private $entry;

	/**
	 * @var array Collection of Member Field models to parse
	 */
	private $member_fields;

	/**
	 * @var array Template variables from ee('Variables/Parser')->extractVariables(), indexed by name
	 */
	private $template_vars;

	/**
	 * Constructor
	 *
	 * @param object $comment EllisLab\ExpressionEngine\Model\Comment\Comment
	 * @param array Collection of Member Field models to parse
	 * @param array Template variables from ee('Variables/Parser')->extractVariables(), indexed by name
	 */
	public function __construct(CommentModel $comment, $member_fields, $template_vars)
	{
		$this->author = ($comment->Author) ?: ee('Model')->make('Member');
		$this->channel = $comment->Channel;
		$this->comment = $comment;
		$this->entry = $comment->Entry;
		$this->member_fields = $member_fields;
		$this->template_vars = $template_vars;

		parent::__construct();
	}

	/**
	 * getTemplateVariables
	 * @return array fully prepped variables to be parsed
	 */
	public function getTemplateVariables()
	{
		if ( ! empty($this->variables))
		{
			return $this->variables;
		}

		ee()->typography->initialize([
			'parse_images'		=> FALSE,
			'allow_headings'	=> FALSE,
			'word_censor'		=> bool_config_item('comment_word_censoring'),
		]);

		$typography_prefs = [
			'text_format'	=> $this->channel->comment_text_formatting,
			'html_format'	=> $this->channel->comment_html_formatting,
			'auto_links'	=> $this->channel->comment_auto_link_urls,
			'allow_img_url' => $this->channel->comment_allow_img_urls,
		];

		$comment_url = parse_config_variables($this->channel->comment_url, ee()->config->get_cached_site_prefs($this->comment->site_id));
		$channel_url = parse_config_variables($this->channel->channel_url, ee()->config->get_cached_site_prefs($this->comment->site_id));
		$base_url = ($this->channel->comment_url) ? $comment_url : $channel_url;

		$this->variables = [
			'allow_comments'              => $this->entry->allow_comments,
			'author'                      => ($this->author->screen_name) ?: $this->comment->name,
			'author_id'                   => $this->comment->author_id,
			'avatar'                      => ($this->getAvatarVariable('url')) ? TRUE : FALSE,
			'avatar_image_height'         => $this->getAvatarVariable('height'),
			'avatar_image_width'          => $this->getAvatarVariable('width'),
			'avatar_url'                  => $this->getAvatarVariable('url'),
			'can_moderate_comment'        => ee('Permission')->has('can_moderate_comments'),
			'channel_id'                  => $this->entry->channel_id,
			'channel_short_name'          => $this->channel->channel_name,
			'channel_title'               => $this->channel->channel_title,
			'channel_url'                 => $channel_url,
			'comment'                     => $this->formatComment($typography_prefs),
			'comment_auto_path'           => $base_url,
			'comment_date'                => $this->date($this->comment->comment_date),
			'comment_entry_id_auto_path'  => $base_url.'/'.$this->comment->entry_id,
			'comment_expiration_date'     => $this->date($this->entry->comment_expiration_date),
			'comment_id'                  => $this->comment->comment_id,
			'comment_path'                => $this->pathVariable($this->entry->entry_id),
			'comment_site_id'             => $this->comment->site_id,
			'comment_stripped'            => $this->protect($this->comment->comment),
			'comment_url'                 => $comment_url,
			'comment_url_title_auto_path' => $base_url.'/'.$this->entry->url_title,
			'comments_disabled'           => $this->isDisabled(),
			'comments_expired'            => (ee()->localize->now > $this->entry->comment_expiration_date),
			'edit_date'                   => $this->date($this->comment->edit_date),
			'editable'                    => $this->isEditable(),
			'email'                       => $this->comment->email,
			'entry_author_id'             => $this->entry->author_id,
			'entry_id'                    => $this->comment->entry_id,
			'entry_id_path'               => $this->pathVariable($this->comment->entry_id),
			'group_id'                    => $this->author->group_id,
			'ip_address'                  => $this->comment->ip_address,
			'is_ignored'                  => $this->isIgnored(),
			'location'                    => $this->comment->location,
			'member_group_id'             => $this->author->group_id,
			'name'                        => $this->comment->name,
			'permalink'                   => ee()->uri->uri_string.'#'.$this->comment->comment_id,
			'signature'                   => $this->typography($this->getSignatureVariable('signature'), $typography_prefs),
			'signature_image'             => ($this->getSignatureVariable('url')) ? TRUE : FALSE,
			'signature_image_height'      => $this->getSignatureVariable('height'),
			'signature_image_url'         => $this->getSignatureVariable('url'),
			'signature_image_width'       => $this->getSignatureVariable('width'),
			'status'                      => $this->getStatus(),
			'title'                       => $this->entry->title,
			'title_permalink'             => $this->pathVariable($this->entry->url_title),
			'url'                         => $this->url($this->comment->url),
			'url_title'                   => $this->entry->url_title,
			'url_title_path'              => $this->pathVariable($this->entry->url_title),
			'username'                    => $this->author->username,
		];

		$this->addCustomMemberFields();
		$this->addMemberSearchPath();

		// have to wait until now to do these, as 'url' could have been overridden by a custom member 'url' variable. Legacy!
		$this->variables['url_as_author'] = $this->getAuthorUrl($this->variables['url']);
		$this->variables['url_or_email'] = ($this->variables['url']) ?: $this->comment->email;
		$this->variables['url_or_email_as_author'] = $this->getAuthorUrl($this->variables['url'], TRUE);
		$this->variables['url_or_email_as_link'] = $this->getAuthorUrl($this->variables['url'], TRUE, FALSE);

		return $this->variables;
	}

	/**
	 * Add parsed custom member fields to the variables array
	 */
	private function addCustomMemberFields()
	{
		$author = $this->author->getValues();

		$fieldtype_row = [
			'channel_html_formatting' => 'safe',
			'channel_auto_link_urls' => 'y',
			'channel_allow_img_urls' => 'n'
		];

		foreach ($this->member_fields as $field)
		{
			$col = 'm_field_id_'.$field->field_id;

			// safety for guest authors and to be defensive
			if (empty($author[$col]) OR ! isset($this->template_vars[$field->field_name]))
			{
				$this->variables[$field->field_name] = '';
				continue;
			}

			// legacy exception for url
			if ($field->field_name == 'url')
			{
				$this->variables['url'] = $this->url($author[$col]);
				continue;
			}

			// date variables, just give the timestamp, template parser will work the rest out
			if ($field->field_type == 'date')
			{
				$this->variables[$field->field_name] = $author[$col];
				continue;
			}

			foreach ($this->template_vars[$field->field_name] as $var)
			{
				$modifier = ($var['modifier'] != '') ? ':'.$var['modifier'] : '';

				$this->variables[$field->field_name.$modifier] = $field->parse(
					$author[$col],
					$this->author->member_id,
					'member',
					$var,
					'{'.$field->field_name.'}', // fake tagdata to force just this variable to be returned
					$fieldtype_row,
					$field->field_name
				);
			}
		}
	}

	/**
	 * Legacy {member_search_path='foo/bar'}
	 * Can't treat it like a normal path variable, because it's not, it's an action URL
	 */
	private function addMemberSearchPath()
	{
		foreach ($this->template_vars as $name => $vars)
		{
			if (strncmp($name, 'member_search_path', 18) === 0)
			{
				$path = ee()->functions->extract_path($name);
				$params = [
					'result_path' => $path,
					'mbr' => $this->comment->author_id,
					'return' => FALSE,
					'token' => FALSE,
				];

				$this->variables[$name] = $this->action('Search', 'do_search', $params);
				return;
			}
		}
	}

	/**
	 * Format the comment, including an extension hook
	 * @param  array $typography_prefs Typography Preferences
	 * @return string Parsed and formatted comment
	 */
	private function formatComment($typography_prefs)
	{
		/* 'comment_entries_comment_format' hook.
		/*  - Play with the contents of the comment entries
		*/
			if (ee()->extensions->active_hook('comment_entries_comment_format') === TRUE)
			{
				return ee()->extensions->call('comment_entries_comment_format', $this->comment->getValues());
			}
		/*
		/* -------------------------------------------*/

		return $this->typography($this->comment->comment, $typography_prefs);
	}

	/**
	 * Get Author URLs
	 *
	 * @param  string  $url The URL to use
	 * @param  boolean $fallback_to_email Whether to fallback to email if the URL is empty
	 * @param  boolean $use_name_in_link  Whether to use the user's name as the visible part of the link or just the URL/Email
	 * @return string parsed author URL variable
	 */
	private function getAuthorUrl($url, $fallback_to_email = FALSE, $use_name_in_link = TRUE)
	{
		if ($url)
		{
			$label = ($use_name_in_link) ? $this->comment->name : $url;
			return '<a href="'.$url.'">'.$label.'</a>';
		}
		elseif ($fallback_to_email && $this->comment->email)
		{
			$label = ($use_name_in_link) ? $this->comment->name : $this->comment->email;
			return ee()->typography->encode_email($this->comment->email, $label);
		}

		return $this->comment->name;
	}

	/**
	 * Access to Avatar-related variables
	 *
	 * Gated since all properties are disabled if avatars are not enabled
	 *
	 * @param  string $property Which property you are after
	 * @return string The parsed requested property
	 */
	private function getAvatarVariable($property)
	{
		if ( ! $this->avatarsEnabled())
		{
			return '';
		}

		switch ($property)
		{
			case 'url':
				return $this->author->getAvatarUrl();
			case 'width':
				return $this->author->avatar_width;
			case 'height':
				return $this->author->avatar_height;
		}

		// er, something wrong?
		return FALSE;
	}

	/**
	 * @return boolean Whether avatars are enabled or not
	 */
	private function avatarsEnabled()
	{
		if ( ! bool_config_item('enable_avatars'))
		{
			return FALSE;
		}

		if ( ! get_bool_from_string(ee()->session->userdata('display_avatars')))
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Access to Signature-related variables
	 *
	 * Gated since all properties are disabled if signatures are not enabled.
	 *
	 * @param  string $property Which property you are after
	 * @return string The parsed requested property
	 */
	private function getSignatureVariable($property)
	{
		if ( ! $this->signaturesEnabled())
		{
			return '';
		}

		switch ($property)
		{
			case 'signature':
				return $this->author->signature;
			case 'url':
				return $this->author->getSignatureImageUrl();
			case 'width':
				return $this->author->sig_img_width;
			case 'height':
				return $this->author->sig_img_height;
		}

		// er, something wrong?
		return FALSE;
	}

	/**
	 * @return boolean Whether signatures are enabled or not
	 */
	private function signaturesEnabled()
	{
		if ( ! bool_config_item('allow_signatures'))
		{
			return FALSE;
		}

		if ( ! get_bool_from_string(ee()->session->userdata('display_signatures')))
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @return string Spelled-out version of the comment status
	 */
	private function getStatus()
	{
		switch ($this->comment->status)
		{
			case 'o':
				return 'open';
			case 'p':
				return 'pending';
			case 'c':
				return 'closed';
		}
	}

	/**
	 * @return boolean Whether comments are disabled
	 */
	private function isDisabled()
	{
		return ($this->entry->allow_comments === FALSE OR
			$this->channel->comment_system_enabled === FALSE OR
			bool_config_item('enable_comments') === FALSE);
	}

	/**
	 * @return boolean Whether the user can edit this comment
	 */
	private function isEditable()
	{
		if (ee('Permission')->has('can_edit_all_comments'))
		{
			return TRUE;
		}

		if ($this->comment->author_id == ee()->session->userdata('member_id') && ee('Permission')->has('can_edit_own_comments'))
		{
			if (ee()->config->item('comment_edit_time_limit') == 0)
			{
				return TRUE;
			}

			$time_limit_sec = 60 * ee()->config->item('comment_edit_time_limit');
			return $this->comment->comment_date > (ee()->localize->now - $time_limit_sec);
		}

		return FALSE;
	}

	/**
	 * @return boolean [Whether the current user is ignoring this commenter
	 */
	private function isIgnored()
	{
		if ( ! $this->comment->author_id)
		{
			return FALSE;
		}

		return in_array($this->comment->author_id, ee()->session->userdata('ignore_list'));
	}
}
// END CLASS

// EOF
