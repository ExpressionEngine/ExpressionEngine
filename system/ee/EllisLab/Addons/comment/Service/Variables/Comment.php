<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
	 * @var string A pre-parsed ACTion URL for member search
	 */
	private $member_search_url;

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
	 * @param string $member_search_url A pre-parsed ACTion URL for member search
	 */
	public function __construct(CommentModel $comment, $member_fields, $template_vars, $member_search_url)
	{
		$this->author = ($comment->Author) ?: ee('Model')->make('Member');
		$this->channel = $comment->Channel;
		$this->comment = $comment;
		$this->entry = $comment->Entry;
		$this->member_fields = $member_fields;
		$this->member_search_url = $member_search_url;
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

		// todo (before PR) allow url and location override from custom member fields
		$commenter_url = (string) ee('Format')->make('Text', $this->comment->url)->url();
		$location = $this->comment->location;

		$this->variables = [
			'allow_comments'              => $this->entry->allow_comments,
			'author'                      => ($this->author->screen_name) ?: $this->comment->name,
			'author_id'                   => $this->comment->author_id,
			'avatar'                      => ($this->getAvatarVariable('url')) ? TRUE : FALSE,
			'avatar_image_height'         => $this->getAvatarVariable('height'),
			'avatar_image_width'          => $this->getAvatarVariable('width'),
			'avatar_url'                  => $this->getAvatarVariable('url'),
			'can_moderate_comment'        => $this->canModerate(),
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
			'member_search_path'          => $this->member_search_url.$this->comment->author_id,
			'name'                        => $this->comment->name,
			'permalink'                   => ee()->uri->uri_string.'#'.$this->comment->comment_id,
			'signature'                   => $this->typography($this->getSignatureVariable('signature'), $typography_prefs),
			'signature_image'             => ($this->getSignatureVariable('url')) ? TRUE : FALSE,
			'signature_image_height'      => $this->getSignatureVariable('height'),
			'signature_image_url'         => $this->getSignatureVariable('url'),
			'signature_image_width'       => $this->getSignatureVariable('width'),
			'status'                      => $this->comment->status,
			'title'                       => $this->entry->title,
			'title_permalink'             => $this->pathVariable($this->entry->url_title),
			'url'                         => $this->comment->url,
			'url_as_author'               => $this->getAuthorUrl(),
			'url_or_email'                => ($this->comment->url) ?: $this->comment->email,
			'url_or_email_as_author'      => $this->getAuthorUrl(TRUE),
			'url_or_email_as_link'        => $this->getAuthorUrl(TRUE, FALSE),
			'url_title'                   => $this->entry->url_title,
			'url_title_path'              => $this->pathVariable($this->entry->url_title),
			'username'                    => $this->author->username,
		];

		$this->addCustomMemberFields();

		return $this->variables;
	}

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

			$this->variables[$field->field_name] = $field->parse(
				$author[$col],
				$this->author->member_id,
				'member',
				$this->template_vars[$field->field_name],
				'{'.$field->field_name.'}', // fake tagdata to force just this variable to be returned
				$fieldtype_row,
				$field->field_name
			);
		}
	}

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

	private function getAuthorUrl($fallback_to_email = FALSE, $use_name_in_link = TRUE)
	{
		if ($this->comment->url)
		{
			$label = ($use_name_in_link) ? $this->comment->name : $this->comment->url;
			return '<a href="'.$this->comment->url.'">'.$label.'</a>';
		}
		elseif ($fallback_to_email && $this->comment->email)
		{
			$label = ($use_name_in_link) ? $this->comment->name : $this->comment->email;
			return ee()->typography->encode_email($this->comment->email, $label);
		}

		return $this->comment->name;
	}

	private function isIgnored()
	{
		if ( ! $this->comment->author_id)
		{
			return FALSE;
		}

		return in_array($this->comment->author_id, ee()->session->userdata('ignore_list'));
	}

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
				if ($this->author->sig_img_filename)
				{
					return ee()->config->slash_item('sig_img_url').$this->author->sig_img_filename;
				}
				return '';
			case 'width':
				return $this->author->sig_img_width;
			case 'height':
				return $this->author->sig_img_height;
		}

		// er, something wrong?
		return FALSE;
	}

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

	private function getAvatarVariable($property)
	{
		if ( ! $this->avatarsEnabled())
		{
			return '';
		}

		switch ($property)
		{
			case 'url':
				if ($this->author->avatar_filename)
				{
					$avatar_url = ee()->config->slash_item('avatar_url');
		            $avatar_fs_path = ee()->config->slash_item('avatar_path');

		            if (file_exists($avatar_fs_path.'default/'.$this->author->avatar_filename))
		            {
		                $avatar_url .= 'default/';
		            }

					return $avatar_url.$this->author->avatar_filename;
				}
				return '';
			case 'width':
				return $this->author->avatar_width;
			case 'height':
				return $this->author->avatar_height;
		}

		// er, something wrong?
		return FALSE;
	}

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

	private function isDisabled()
	{
		return ($this->entry->allow_comments === FALSE OR
			$this->channel->comment_system_enabled === FALSE OR
			bool_config_item('enable_comments') === FALSE);
	}

	private function canModerate()
	{
		if (ee('Permission')->has('can_edit_all_comments'))
		{
			return TRUE;
		}

		if ($this->ee('Permission')->has('can_edit_own_comments') &&
			$this->entry->author_id == ee()->session->userdata('member_id'))
		{
			return TRUE;
		}

		return FALSE;
	}

	private function isEditable()
	{
		if ($this->canModerate())
		{
			return TRUE;
		}

		if ($this->comment->author_id == ee()->session->userdata('member_id'))
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
}
// END CLASS

// EOF
