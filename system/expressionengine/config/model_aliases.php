<?php

return array(

	# \EllisLab\ExpressionEngine\Model..

		// ..\Addon
		'Accessory' => '\EllisLab\ExpressionEngine\Model\Addon\Accessory',
		'AccessoryGateway' => '\EllisLab\ExpressionEngine\Model\Addon\Gateway\AccessoryGateway',
		'Extension' => '\EllisLab\ExpressionEngine\Model\Addon\Extension',
		'ExtensionGateway' => '\EllisLab\ExpressionEngine\Model\Addon\Gateway\ExtensionGateway',
		'Module' => '\EllisLab\ExpressionEngine\Model\Addon\Module',
		'ModuleGateway' => '\EllisLab\ExpressionEngine\Model\Addon\Gateway\ModuleGateway',

		// ..\Category
		'Category' => '\EllisLab\ExpressionEngine\Model\Category\Category',
		'CategoryFieldDataGateway' => '\EllisLab\ExpressionEngine\Model\Category\Gateway\CategoryFieldDataGateway',
		'CategoryGateway' => '\EllisLab\ExpressionEngine\Model\Category\Gateway\CategoryGateway',
		'CategoryGroup' => '\EllisLab\ExpressionEngine\Model\Category\CategoryGroup',
		'CategoryGroupGateway'=> '\EllisLab\ExpressionEngine\Model\Category\Gateway\CategoryGroupGateway',

		// ..\File
		'UploadDestination' => '\EllisLab\ExpressionEngine\Model\File\UploadDestination',
		'UploadPrefGateway' => '\EllisLab\ExpressionEngine\Model\File\Gateway\UploadPrefGateway',
		'FileDimension' => '\EllisLab\ExpressionEngine\Model\File\FileDimension',
		'FileDimensionGateway' => '\EllisLab\ExpressionEngine\Model\File\Gateway\FileDimensionGateway',

		// ..\Log
		'CpLog' => '\EllisLab\ExpressionEngine\Model\Log\CpLog',
		'CpLogGateway' => '\EllisLab\ExpressionEngine\Model\Log\Gateway\CpLogGateway',
		'DeveloperLog' => '\EllisLab\ExpressionEngine\Model\Log\DeveloperLog',
		'DeveloperLogGateway' => '\EllisLab\ExpressionEngine\Model\Log\Gateway\DeveloperLogGateway',
		'EmailConsoleCache' => '\EllisLab\ExpressionEngine\Model\Log\EmailConsoleCache',
		'EmailConsoleCacheGateway' => '\EllisLab\ExpressionEngine\Model\Log\Gateway\EmailConsoleCacheGateway',

		// ..\Security
		'Throttle' => '\EllisLab\ExpressionEngine\Model\Security\Throttle',
		'ThrottleGateway' => '\EllisLab\ExpressionEngine\Model\Security\Gateway\ThrottleGateway',
		'ResetPassword' => '\EllisLab\ExpressionEngine\Model\Security\ResetPassword',
		'ResetPasswordGateway' => '\EllisLab\ExpressionEngine\Model\Security\Gateway\ResetPasswordGateway',

		// ..\Session
		// empty

		// ..\Site
		'Site' => '\EllisLab\ExpressionEngine\Model\Site\Site',
		'SiteGateway' => '\EllisLab\ExpressionEngine\Model\Site\Gateway\SiteGateway',

		// ..\Status
		'Status' => '\EllisLab\ExpressionEngine\Model\Status\Status',
		'StatusGateway' => '\EllisLab\ExpressionEngine\Model\Status\Gateway\StatusGateway',
		'StatusGroup' => '\EllisLab\ExpressionEngine\Model\Status\StatusGroup',
		'StatusGroupGateway' => '\EllisLab\ExpressionEngine\Model\Status\Gateway\StatusGroupGateway',

		// ..\Template
		'Template' => '\EllisLab\ExpressionEngine\Model\Template\Template',
		'TemplateGroup'  => '\EllisLab\ExpressionEngine\Model\Template\TemplateGroup',
		'TemplateGateway' => '\EllisLab\ExpressionEngine\Model\Template\Gateway\TemplateGateway',
		'TemplateGroupGateway' => '\EllisLab\ExpressionEngine\Model\Template\Gateway\TemplateGroupGateway',

	# \EllisLab\ExpressionEngine\Module..

		// ..\Channel
		'Channel' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Channel',
		'ChannelFieldGroup'=> '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelFieldGroup',
		'ChannelFieldGroupGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelFieldGroupGateway',
		'ChannelFieldStructure' => '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelFieldStructure',
		'ChannelFieldGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelFieldGateway',
		'ChannelEntry' => '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelEntry',
		'ChannelGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelGateway',
		'ChannelTitleGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelTitleGateway',
		'ChannelDataGateway' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Gateway\ChannelDataGateway',

		// ..\Comment
		'Comment' => '\EllisLab\ExpressionEngine\Module\Comment\Model\Comment',
		'CommentGateway' => '\EllisLab\ExpressionEngine\Module\Comment\Model\Gateway\CommentGateway',
		'CommentSubscription' => '\EllisLab\ExpressionEngine\Module\Comment\Model\CommentSubscription',
		'CommentSubscriptionGateway' => '\EllisLab\ExpressionEngine\Module\Comment\Model\Gateway\CommentSubscriptionGateway',

		// ..\MailingList
		'MailingList' => '\EllisLab\ExpressionEngine\Module\MailingList\Model\MailingList',
		'MailingListGateway' => '\EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway\MailingListGateway',
		'MailingListQueue' => '\EllisLab\ExpressionEngine\Module\MailingList\Model\MailingListQueue',
		'MailingListQueueGateway' => '\EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway\MailingListQueueGateway',
		'MailingListUser' => '\EllisLab\ExpressionEngine\Module\MailingList\Model\MailingListUser',
		'MailingListUserGateway' => '\EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway\MailingListUserGateway',

		// ..\Member
		'Member' => '\EllisLab\ExpressionEngine\Module\Member\Model\Member',
		'MemberGroup' => '\EllisLab\ExpressionEngine\Module\Member\Model\MemberGroup',
		'MemberGateway' => '\EllisLab\ExpressionEngine\Module\Member\Model\Gateway\MemberGateway',
		'MemberGroupGateway' => '\EllisLab\ExpressionEngine\Module\Member\Model\Gateway\MemberGroupGateway',

		// ..\RichTextEditor
		'RichTextEditorTool' => '\EllisLab\ExpressionEngine\Module\RichTextEditor\Model\RichTextEditorTool',
		'RichTextEditorToolGateway' => '\EllisLab\ExpressionEngine\Module\RichTextEditor\Model\Gateway\RichTextEditorToolGateway',
		'RichTextEditorToolset' => '\EllisLab\ExpressionEngine\Module\RichTextEditor\Model\RichTextEditorToolset',
		'RichTextEditorToolsetGateway' => '\EllisLab\ExpressionEngine\Module\RichTextEditor\Model\Gateway\RichTextEditorToolsetGateway',

		// ..\Search
		'SearchLog' => '\EllisLab\ExpressionEngine\Module\Search\Model\SearchLog',
		'SearchLogGateway' => '\EllisLab\ExpressionEngine\Module\Search\Model\Gateway\SearchLogGateway',

		// TODO: FIND A NEW HOME FOR THESE
		'EmailCache' => '\EllisLab\ExpressionEngine\Model\EmailCache',
		'EmailCacheGateway' => '\EllisLab\ExpressionEngine\Model\Gateway\EmailCacheGateway',
);
