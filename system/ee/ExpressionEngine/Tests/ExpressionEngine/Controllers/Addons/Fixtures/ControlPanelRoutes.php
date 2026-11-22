<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Addons\Fixtures {
    class FieldtypeMcp extends \ExpressionEngine\Service\Addon\Mcp
    {
        protected $route_namespace = __NAMESPACE__;
    }

    class IndexMcp extends FieldtypeMcp
    {
        protected $route_namespace = __NAMESPACE__ . '\\WithIndex';
    }

    class CustomMethodMcp extends FieldtypeMcp
    {
        public function tryRoute($domain, array $params = [])
        {
            return ['ajax' => true, 'body' => 'Custom method ' . $domain];
        }
    }

    class LocalizedMcp extends FieldtypeMcp
    {
        public static $constructorHeading;

        public function __construct()
        {
            self::$constructorHeading = ee()->lang->line('test_fieldtype_heading');
        }
    }

    class LegacyMcp
    {
        public function preview_data()
        {
            return ['ajax' => true, 'body' => 'legacy module response'];
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Addons\Fixtures\ControlPanel\Routes {
    class PreviewData extends \ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute
    {
        public static $processedIds = [];

        protected $route_path = 'preview_data';

        public function process($id = false)
        {
            self::$processedIds[] = $id;
            $this->setBody(json_encode(['success' => true, 'id' => $id]));

            return $this;
        }

        public function toArray()
        {
            return array_merge(parent::toArray(), ['ajax' => true]);
        }
    }

    class TryRoute extends PreviewData
    {
    }

    class Localized extends \ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute
    {
        public function process($id = false)
        {
            return $this->setHeading(ee()->lang->line('test_fieldtype_heading'));
        }
    }

    class Details extends \ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute
    {
        protected $route_path = 'details';

        public function process($id = false)
        {
            $this->setHeading('Fieldtype details')->setBody('Details for ' . $id);
            $this->breadcrumbs = ['/cp/details' => 'Details'];

            return $this;
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Addons\Fixtures\WithIndex\ControlPanel\Routes {
    class Index extends \ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute
    {
        public function process($id = false)
        {
            return $this->setBody('Modern index');
        }
    }
}

namespace {
    class Test_fieldtype_ext
    {
        public function settings()
        {
            return [];
        }
    }
}
