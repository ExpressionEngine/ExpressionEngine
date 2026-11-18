<?php

namespace ExpressionEngine\Tests\Model\File;

use ExpressionEngine\Model\File\FileDimension;
use ExpressionEngine\Model\File\UploadDestination;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Facade;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class UploadDestinationTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        ee()->resetMocks();
        parent::tearDown();
    }

    /** @dataProvider keptManipulationsProvider */
    public function testUpdateOnlyDeletesRemovedManipulations($ids, $removedIds)
    {
        $kept = array();
        foreach ($ids as $id) {
            $dimension = new FileDimension();
            $dimension->setRawProperty('id', $id);
            $kept[] = $dimension;
        }
        $destination = new UploadDestinationWithManipulations(new Collection($kept));
        $query = m::mock();
        $query->shouldReceive('filter')->with('upload_location_id', 7)->once()->andReturnSelf();
        $query->shouldReceive('all')->once()->andReturnSelf();
        $query->shouldReceive('indexBy')->with('id')->once()->andReturn(array(11 => new \stdClass(), 12 => new \stdClass()));
        $model = m::mock(Facade::class);
        $model->shouldReceive('get')->with('FileDimension')->once()->andReturn($query);
        foreach ($removedIds as $id) {
            $dimension = m::mock();
            $dimension->shouldReceive('onAfterDelete')->once();
            $removedQuery = m::mock();
            $removedQuery->shouldReceive('first')->once()->andReturn($dimension);
            $model->shouldReceive('get')->with('FileDimension', $id)->once()->andReturn($removedQuery);
        }
        ee()->setMock('Model', $model);

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL);
        try {
            $destination->onAfterUpdate();
        } finally {
            restore_error_handler();
        }
    }

    public function keptManipulationsProvider()
    {
        return array(
            'new manipulation and retained ID' => array(array(null, 11), array(12)),
            'multiple new manipulations' => array(array(null, null, 11, 12), array()),
            'legacy empty ID' => array(array('', 11), array(12)),
            'retained numeric string ID' => array(array('11'), array(12)),
            'all existing manipulations retained' => array(array(11, 12), array()),
            'all existing manipulations removed' => array(array(), array(11, 12)),
        );
    }
}

class UploadDestinationWithManipulations extends UploadDestination
{
    public function __construct(Collection $manipulations)
    {
        $this->id = 7;
        $this->_manipulationsToOperate = $manipulations;
    }
}
