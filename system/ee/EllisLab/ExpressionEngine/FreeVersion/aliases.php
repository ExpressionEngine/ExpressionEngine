<?php

// These aliases replace the original implementations transparently. Our
// code can continue to use the original class names for creation and type
// hinting, but the autoloader will receive the overriden class name. Magic.

class_alias(
	'EllisLab\ExpressionEngine\FreeVersion\Spam\Spam',
	'EllisLab\Addons\Spam\Service\Spam'
);
