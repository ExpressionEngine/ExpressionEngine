<?php

namespace ExpressionEngine\Tests\Model\Content\Display;

use ExpressionEngine\Model\Content\Display\LayoutTab;
use ExpressionEngine\Model\Content\FieldFacade;
use ExpressionEngine\Service\Alert\AlertCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class LayoutTabTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
        m::close();
        parent::tearDown();
    }

    /** @dataProvider statusProvider */
    public function testStatusLookupPreservesKeysWithoutDeprecations($status, array $items, $warn)
    {
        $field = m::mock(FieldFacade::class);
        $field->shouldReceive('getId')->andReturn('status');
        $field->shouldReceive('getData')->andReturn($status);
        $field->shouldReceive('get')->with('field_list_items')->andReturn($items);

        $alerts = m::mock(AlertCollection::class);
        if ($warn) {
            $alert = m::mock();
            foreach (array('asWarning', 'cannotClose', 'withTitle', 'addToBody') as $method) {
                $alert->shouldReceive($method)->once()->andReturnSelf();
            }
            $alert->shouldReceive('now')->once();
            $alerts->shouldReceive('makeInline')->with('status-not-available')->once()->andReturn($alert);
        } else {
            $alerts->shouldNotReceive('makeInline');
        }
        ee()->setMock('CP/Alert', $alerts);

        $tab = new LayoutTab('publish', 'Publish');
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL);
        try {
            $this->assertSame($tab, $tab->addField($field));
            $this->assertSame(array($field), $tab->getFields());
        } finally {
            restore_error_handler();
        }
    }

    public function statusProvider()
    {
        return array(
            'null status unavailable' => array(null, array('open' => 'Open'), true),
            'null uses empty key even with null value' => array(null, array('' => null), false),
            'empty status unavailable' => array('', array('open' => 'Open'), true),
            'empty status available' => array('', array('' => 'No status'), false),
            'available status' => array('open', array('open' => 'Open'), false),
            'removed status' => array('closed', array('open' => 'Open'), true),
            'available status with null value' => array('open', array('open' => null), false),
        );
    }
}
