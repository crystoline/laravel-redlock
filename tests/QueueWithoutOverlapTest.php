<?php

namespace Crystoline\RedLock\Traits;

use Crystoline\RedLock\Facades\RedLock;
use Mockery;
use TestCase;

class QueueWithoutOverlapTest extends TestCase
{
    public function testInstanciate()
    {
        new QueueWithoutOverlapJob();
    }

    public function testAllOfIt()
    {
        $job = new QueueWithoutOverlapJob();

        $queue = Mockery::mock();
        $queue->shouldReceive('push')->with($job)->once();

        RedLock::shouldReceive('lock')
            ->with("Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:", 1000000)
            ->twice()
            ->andReturn(['resource' => 'Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:']);
        RedLock::shouldReceive('unlock')
            ->with(['resource' => 'Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:'])
            ->twice()
            ->andReturn(true);

        $job->queue($queue, $job);

        $job->handle();

        $this->assertTrue($job->ran);
    }

    public function testFailToLock()
    {
        $job = new QueueWithoutOverlapJob();

        $queue = Mockery::mock();

        RedLock::shouldReceive('lock')
            ->with("Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:", 1000000)
            ->once()
            ->andReturn(false);

        $id = $job->queue($queue, $job);

        $this->assertFalse($id);
    }

    public function testFailToRefresh()
    {
        $job = new QueueWithoutOverlapJob();

        $queue = Mockery::mock();
        $queue->shouldReceive('push')->with($job)->once();

        RedLock::shouldReceive('lock')
            ->with("Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:", 1000000)
            ->twice()
            ->andReturn(
                ['resource' => 'Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:'],
                false
            );
        RedLock::shouldReceive('unlock')
            ->with(['resource' => 'Crystoline\RedLock\Traits\QueueWithoutOverlapJob::1000:'])
            ->once()
            ->andReturn(true);

        $job->queue($queue, $job);

        $this->expectException('Crystoline\RedLock\Exceptions\QueueWithoutOverlapRefreshException');

        $job->handle();
    }

    public function testAllOfItDefaultLockTime()
    {
        $job = new QueueWithoutOverlapJobDefaultLockTime();

        $queue = Mockery::mock();
        $queue->shouldReceive('push')->with($job)->once();

        RedLock::shouldReceive('lock')
            ->with("Crystoline\RedLock\Traits\QueueWithoutOverlapJobDefaultLockTime::", 300000)
            ->twice()
            ->andReturn(['resource' => "Crystoline\RedLock\Traits\QueueWithoutOverlapJobDefaultLockTime::"]);
        RedLock::shouldReceive('unlock')
            ->with(['resource' => "Crystoline\RedLock\Traits\QueueWithoutOverlapJobDefaultLockTime::"])
            ->twice()
            ->andReturn(true);

        $job->queue($queue, $job);

        $job->handle();

        $this->assertTrue($job->ran);
    }
}
