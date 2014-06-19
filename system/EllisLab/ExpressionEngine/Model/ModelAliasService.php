<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Core\AliasService;

class ModelAliasService extends AliasService {

	protected $identifier = 'Model';

	protected $aliases = array(
		'Template' => '\EllisLab\ExpressionEngine\Model\Template\Template',
		'TemplateGroup'  => '\EllisLab\ExpressionEngine\Model\Template\TemplateGroup',
		'TemplateGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\TemplateGateway',
		'TemplateGroupGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\TemplateGroupGateway',
		'Channel' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Channel',
		'ChannelFieldGroup'=> '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelFieldGroup',
		'ChannelFieldGroupGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelFieldGroupGateway',
		'ChannelFieldStructure' => '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelFieldStructure',
		'ChannelFieldGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelFieldGateway',
		'ChannelEntry' => '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelEntry',
		'ChannelGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelGateway',
		'ChannelTitleGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelTitleGateway',
		'ChannelDataGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelDataGateway',
		'Member' => '\EllisLab\ExpressionEngine\Module\Member\Model\Member',
		'MemberGroup' => '\EllisLab\ExpressionEngine\Module\Member\Model\MemberGroup',
		'MemberGateway' => '\EllisLab\ExpressionEngine\Module\Member\Model\Gateway\MemberGateway',
		'MemberGroupGateway' => '\EllisLab\ExpressionEngine\Module\Member\Model\Gateway\MemberGroupGateway',
		'ResetPassword' => '\EllisLab\ExpressionEngine\Module\Member\Model\ResetPassword',
		'ResetPasswordGateway' => '\EllisLab\ExpressionEngine\Module\Member\Model\Gateway\ResetPasswordGateway',
		'Category' => '\EllisLab\ExpressionEngine\Model\Category\Category',
		'CategoryFieldDataGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\CategoryFieldDataGateway',
		'CategoryGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\CategoryGateway',
		'CategoryGroup' => '\EllisLab\ExpressionEngine\Model\Category\CategoryGroup',
		'CategoryGroupGateway'=> '\EllisLab\ExpressionEngine\Model\Gateway\CategoryGroupGateway',
		'Status' => '\EllisLab\ExpressionEngine\Model\Status',
		'StatusGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\StatusGateway',
		'StatusGroup' => '\EllisLab\ExpressionEngine\Model\StatusGroup',
		'StatusGroupGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\StatusGroupGateway',
		'Site' => '\EllisLab\ExpressionEngine\Model\Site',
		'SiteGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\SiteGateway',
		'CpLog' => '\EllisLab\ExpressionEngine\Model\CpLog',
		'CpLogGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\CpLogGateway',
		'EmailConsoleCache' => '\EllisLab\ExpressionEngine\Model\EmailConsoleCache',
		'EmailConsoleCacheGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\EmailConsoleCacheGateway',
		'DeveloperLog' => '\EllisLab\ExpressionEngine\Model\DeveloperLog',
		'DeveloperLogGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\DeveloperLogGateway',
		'SearchLog' => '\EllisLab\ExpressionEngine\Module\Search\Model\SearchLog',
		'SearchLogGateway' => '\EllisLab\ExpressionEngine\Module\Search\Model\Gateway\SearchLogGateway',
		'Throttle' => '\EllisLab\ExpressionEngine\Model\Throttle',
		'ThrottleGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\ThrottleGateway'
	);
}