<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Msm {
    /**
     * Stop controller execution when access is denied.
     *
     * @param string $message
     * @param int $statusCode
     * @return void
     */
    function show_error($message, $statusCode = 500)
    {
        throw new \ExpressionEngine\Tests\Controllers\Msm\MsmAccessDeniedException($message, $statusCode);
    }
}

namespace ExpressionEngine\Tests\Controllers\Msm {
    use ExpressionEngine\Controller\Msm\Msm;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;
    use ReflectionMethod;
    use RuntimeException;
    use stdClass;

    class MsmTest extends TestCase
    {
        /**
         * Load the legacy controller base class.
         *
         * @return void
         */
        public static function setUpBeforeClass(): void
        {
            require_once APPPATH . 'core/Controller.php';
            require_once SYSPATH . 'ee/ExpressionEngine/Controller/Msm/Msm.php';
        }

        /**
         * Reset service mocks between tests.
         *
         * @return void
         */
        protected function tearDown(): void
        {
            ee()->resetMocks();
        }

        /**
         * Require site-management access and an assigned site.
         *
         * @dataProvider removalAccessProvider
         * @param bool $canManageSites
         * @param int[] $assignedSiteIds
         * @param int[] $selectedSiteIds
         * @return void
         */
        public function testRemoveChecksSiteAccess($canManageSites, array $assignedSiteIds, array $selectedSiteIds)
        {
            $permission = $this->getMockBuilder(stdClass::class)->addMethods(array('can'))->getMock();
            $permission->method('can')->with('admin_sites')->willReturn($canManageSites);
            ee()->setMock('Permission', $permission);

            $session = $this->getMockBuilder(stdClass::class)->addMethods(array('userdata'))->getMock();
            $session->method('userdata')->with('assigned_sites')->willReturn(array_fill_keys($assignedSiteIds, 'Site'));
            ee()->setMock('session', $session);

            $model = $this->getMockBuilder(stdClass::class)->addMethods(array('get'))->getMock();
            $model->expects($this->never())->method('get');
            ee()->setMock('Model', $model);

            $this->expectException(MsmAccessDeniedException::class);
            $this->expectExceptionCode(403);

            $this->remove($selectedSiteIds);
        }

        /**
         * Provide site-removal access combinations.
         *
         * @return array
         */
        public static function removalAccessProvider()
        {
            return array(
                'site management unavailable' => array(false, array(2), array(2)),
                'site outside assignment' => array(true, array(2), array(2, 3)),
            );
        }

        /**
         * Allow an assigned site to reach collection deletion.
         *
         * @return void
         */
        public function testRemoveAllowsAssignedSite()
        {
            $permission = $this->getMockBuilder(stdClass::class)->addMethods(array('can'))->getMock();
            $permission->method('can')->with('admin_sites')->willReturn(true);
            ee()->setMock('Permission', $permission);

            $session = $this->getMockBuilder(stdClass::class)->addMethods(array('userdata'))->getMock();
            $session->method('userdata')->with('assigned_sites')->willReturn(array(2 => 'Second site'));
            ee()->setMock('session', $session);

            $sites = $this->getMockBuilder(stdClass::class)->addMethods(array('pluck', 'delete'))->getMock();
            $sites->method('pluck')->with('site_label')->willReturn(array('Second site'));
            $sites->expects($this->once())->method('delete')->willThrowException(new MsmDeletionReachedException());

            $query = $this->getMockBuilder(stdClass::class)->addMethods(array('all'))->getMock();
            $query->method('all')->willReturn($sites);

            $model = $this->getMockBuilder(stdClass::class)->addMethods(array('get'))->getMock();
            $model->expects($this->once())->method('get')->with('Site', array(2))->willReturn($query);
            ee()->setMock('Model', $model);

            $this->expectException(MsmDeletionReachedException::class);

            $this->remove(array(2));
        }

        /**
         * Invoke the site-removal controller action.
         *
         * @param int[] $siteIds
         * @return void
         */
        private function remove(array $siteIds)
        {
            $controller = (new ReflectionClass(Msm::class))->newInstanceWithoutConstructor();
            $method = new ReflectionMethod(Msm::class, 'remove');
            \TestReflectionHelper::makeMethodAccessible($method);
            $method->invoke($controller, $siteIds);
        }
    }

    class MsmAccessDeniedException extends RuntimeException
    {
    }

    class MsmDeletionReachedException extends RuntimeException
    {
    }
}
