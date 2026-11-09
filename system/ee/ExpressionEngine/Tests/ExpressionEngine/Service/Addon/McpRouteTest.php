<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon {
    class McpRouteNotFoundException extends \RuntimeException
    {
    }

    if (!function_exists(__NAMESPACE__ . '\\show_404')) {
        function show_404()
        {
            throw new McpRouteNotFoundException('Route not found', 404);
        }
    }
}

namespace ExpressionEngine\Tests\Service\Addon\RouteFixtures\ControlPanel\Routes {
    class PreviewData extends \ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute
    {
        public function process($id = false)
        {
            return $this->setHeading('Preview')->setBody('Entry ' . $id);
        }
    }

    class InvalidRoute
    {
    }
}

namespace ExpressionEngine\Tests\Service\Addon\RouteFixtures\Mcp {
    class Legacy extends \ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute
    {
        public function process($id = false)
        {
            return $this->setBody('Legacy ' . $id);
        }
    }
}

namespace ExpressionEngine\Tests\Service\Addon {
    use ExpressionEngine\Service\Addon\Mcp;
    use ExpressionEngine\Service\Addon\McpRouteNotFoundException;
    use PHPUnit\Framework\TestCase;

    class McpRouteTest extends TestCase
    {
        private function makeController()
        {
            return (new Mcp())->setAddonName('test_fieldtype')->setRouteNamespace(__NAMESPACE__ . '\\RouteFixtures');
        }

        public function testTryRouteDispatchesModernRouteAndPassesItsId()
        {
            $this->assertSame([
                'heading' => 'Preview',
                'breadcrumb' => [],
                'body' => 'Entry 42',
            ], $this->makeController()->tryRoute('preview_data', [42]));
        }

        public function testRouteStillDispatchesHyphenatedModernRoute()
        {
            $result = $this->makeController()->route('preview-data', [42]);

            $this->assertSame('Entry 42', $result['body']);
        }

        public function testTryRoutePreservesLegacyMcpDirectorySupport()
        {
            $result = $this->makeController()->tryRoute('legacy', [42]);

            $this->assertSame('Legacy 42', $result['body']);
        }

        public function testTryRouteReturnsNullWhenRouteIsMissing()
        {
            $this->assertNull($this->makeController()->tryRoute('missing'));
        }

        public function testTryRouteRejectsClassesThatAreNotRoutes()
        {
            $this->assertNull($this->makeController()->tryRoute('invalid_route'));
        }

        public function testRouteStillReturns404WhenRouteIsMissing()
        {
            $this->expectException(McpRouteNotFoundException::class);
            $this->expectExceptionCode(404);

            $this->makeController()->route('missing');
        }
    }
}
