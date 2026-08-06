<?php

namespace ExpressionEngine\Tests\Addons\Rte;

require_once PATH_ADDONS . 'rte/Service/RteService.php';
require_once PATH_ADDONS . 'rte/Service/AbstractRteService.php';
require_once PATH_ADDONS . 'rte/Service/CkeditorService.php';

use ExpressionEngine\Addons\Rte\Service\CkeditorService;
use PHPUnit\Framework\TestCase;

class CkeditorServiceTest extends TestCase
{
    public function testConfigRegexUsesToolsetHandleForAllConfigPaths()
    {
        $config = [
            'htmlSupport' => (object) [
                'allow' => [
                    (object) [
                        'name' => '/^(div|span)$/',
                        'attributes' => ['/^data-[\\w-]+$/', 'title'],
                    ],
                ],
            ],
            'typing' => (object) [
                'transformations' => (object) [
                    'extra' => [
                        (object) [
                            'from' => '/^(foo)$/',
                            'to' => ['bar'],
                        ],
                    ],
                ],
            ],
        ];

        $service = new TestableCkeditorService();

        $this->assertSame(
            [
                'EE.Rte.configs.Smart_Quotes5.htmlSupport.allow[0].name = new RegExp(/^(div|span)$/);',
                'EE.Rte.configs.Smart_Quotes5.htmlSupport.allow[0].attributes[0] = new RegExp(/^data-[\\w-]+$/);',
                'EE.Rte.configs.Smart_Quotes5.typing.transformations.extra[0].from = new RegExp(/^(foo)$/);',
            ],
            $service->buildConfigRegexForTest($config, 'Smart_Quotes5')
        );
    }
}

class TestableCkeditorService extends CkeditorService
{
    public function buildConfigRegexForTest($config, $configHandle)
    {
        return $this->buildConfigRegex($config, $configHandle);
    }
}
