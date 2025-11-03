<?php

// Stub classes for testing Memcached driver without requiring actual extensions

if (!class_exists('MemcachedStub', false)) {
class MemcachedStub
{
    public $data = [];

    public function addServer($host, $port, $weight = 0)
    {
        // No-op for testing
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->data[$key] = $value;
        return true;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    public function delete($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    public function getStats()
    {
        return [
            'localhost:11211' => [
                'time' => time(),
                'uptime' => 3600,
                'version' => '1.6.9'
            ]
        ];
    }
}
}

if (!class_exists('MemcacheStub', false)) {
class MemcacheStub
{
    public $data = [];

    public function addServer($host, $port, $persistent = true, $weight = 1)
    {
        // No-op for testing
    }

    public function set($key, $value, $flags = 0, $ttl = 0)
    {
        $this->data[$key] = $value;
        return true;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    public function delete($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    public function getExtendedStats()
    {
        return [
            'localhost:11211' => [
                'time' => time(),
                'uptime' => 3600,
                'version' => '3.0.8'
            ]
        ];
    }
}
}

// Create global classes when real extensions don't exist
if (!class_exists('Memcached', false)) {
    class Memcached extends MemcachedStub {}
}
if (!class_exists('Memcache', false)) {
    class Memcache extends MemcacheStub {}
}
