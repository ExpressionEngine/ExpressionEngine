<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Spam\Service\Spam;
use ExpressionEngine\Addons\Spam\Service\Training;
use ExpressionEngine\Addons\Spam\Service\Update;

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'ExpressionEngine Spam Module',
    'description' => 'Block spammy comments, forum posts, and member registrations',
    'version' => '2.1.0',
    'namespace' => 'ExpressionEngine\Addons\Spam',
    'settings_exist' => true,
    'services' => array(
        'Core' => 'Service\Spam',
        'Training' => function ($ee, $kernel) {
            $kernel = empty($kernel) ? 'default' : $kernel;

            return new Training($kernel);
        },
        'Update' => 'Service\Update',
        'Classifier' => 'Library\Classifier',
        'Collection' => 'Library\Collection',
        'Distribution' => 'Library\Distribution',
        'Document' => 'Library\Document',
        'Expectation' => 'Library\Expectation',
        'Source' => 'Library\Source',
        'Tokenizer' => 'Library\Tokenizer',
        'Vectorize' => 'Library\Vectorize',
        'Vectorizers/ASCIIPrintable' => 'Library\Vectorizers\ASCIIPrintable',
        'Vectorizers/Entropy' => 'Library\Vectorizers\Entropy',
        'Vectorizers/Links' => 'Library\Vectorizers\Links',
        'Vectorizers/Punctuation' => 'Library\Vectorizers\Punctuation',
        'Vectorizers/Spaces' => 'Library\Vectorizers\Spaces',
        'Vectorizers/Tfidf' => 'Library\Vectorizers\Tfidf',
    ),
    'models' => array(
        'SpamKernel' => 'Model\SpamKernel',
        'SpamParameter' => 'Model\SpamParameter',
        'SpamVocabulary' => 'Model\SpamVocabulary',
        'SpamTraining' => 'Model\SpamTraining',
        'SpamTrap' => 'Model\SpamTrap',
    ),
    'models.dependencies' => array(
        'SpamTrap' => array(
            'ee:Member'
        ),
        'SpamTraining' => array(
            'ee:Member'
        )
    )
);

// EOF
