<?php

namespace ExpressionEngine\Addons\Queue\Services;

use ExpressionEngine\Addons\Queue\Exceptions\QueueException;
use ExpressionEngine\Addons\Queue\Models\Job;
use ExpressionEngine\Addons\Queue\Services\SerializerService;

class QueueService
{
    protected $success = false;
    protected $step = 'init';
    protected $message;
    protected $job;
    protected $jobClass;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    public function fire()
    {
        $payload = $this->job->payload();

        // Serialize queued job
        $jobClass = $this->serialize($payload);

        if ($this->success == false) {
            return $this->result();
        }

        // Run job
        $this->handle($jobClass);

        return $this->result();
    }

    protected function serialize($payload)
    {
        $serializer = new SerializerService();

        // Unserialize class and reinitialize
        $this->step = 'unserialize';

        try {
            $jobClass = $serializer->unserialize($payload);
        } catch (Exception $e) {
            $this->message = $e->getMessage();

            return false;
        } catch (RuntimeException $e) {
            $this->message = $e->getMessage();

            return false;
        } catch (QueueException $e) {
            $this->message = $e->getMessage();

            return false;
        }

        $this->success = true;

        return $jobClass;
    }

    protected function handle($jobClass)
    {
        $this->step == 'execution';
        $this->jobClass = $jobClass;
        $this->success = false;

        try {
            $result = $jobClass->handle();
        } catch (Exception $e) {
            $this->message = $e->getMessage();
        } catch (RuntimeException $e) {
            $this->message = $e->getMessage();
        } catch (QueueException $e) {
            $this->message = $e->getMessage();
        }

        $this->success = true;
        $this->step = 'complete';

        return true;
    }

    private function result()
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'step' => $this->step,
            'job' => $this->job,
            'jobClass' => $this->jobClass,
        ];
    }
}
