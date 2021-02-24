<?php

namespace ExpressionEngine\Addons\Queue\Traits;

use ExpressionEngine\Addons\Queue\Services\SerializerService;

trait Queueable
{
    protected $jobId;
    protected $attemptsTaken = 0;
    protected $className;
    protected $runAt;
    protected $uuid;

    public function construct()
    {
        ee()->load->library('localize');
        ee()->load->helper('language');
        ee()->load->helper('string');

        $this->uuid = uuid4();
        $this->className = get_class($this);
    }

    public function create()
    {
        $job = ee('Model')->make('queue:Job');
        $job->payload = $this->serialize();
        $job->attempts = $this->attempts();
        $job->save();
    }

    public function fail($exception)
    {
        $this->attemptsTaken++;

        if ($this->attemptsTaken < $this->attempts()) {
            return $this->handle();
        }

        $job = ee('Model')->get('queue:Job')
            ->filter('job_id', $this->jobId)
            ->first();

        if ($job) {
            $job->delete();
        }

        $failedJob = ee('Model')->make('queue:FailedJob');
        $failedJob->payload = $this->serialize();
        $failedJob->error = json_encode($exception);
        $failedJob->failed_at = ee()->localize->format_date('%Y-%m-%d %H:%i:%s', ee()->localize->now, ee()->config->item('default_site_timezone'));
        $failedJob->save();
    }

    public function attempts()
    {
        return $this->attempts ?: 1;
    }

    public function sleep()
    {
        return $this->sleep ?: 1;
    }

    public function runAt()
    {
        return $this->runAt ?: ee()->localize->format_date('%Y-%m-%d %H:%i:%s', ee()->localize->now, ee()->config->item('default_site_timezone'));
    }

    protected function serialize()
    {
        $serializer = new SerializerService();

        return $serializer->serialize($this);
    }
}
