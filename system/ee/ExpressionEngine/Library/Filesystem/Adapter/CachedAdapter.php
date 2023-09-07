<?php

namespace ExpressionEngine\Library\Filesystem\Adapter;

use ExpressionEngine\Dependency\League\Flysystem;

/**
 * Adapter Trait
 *
 * Commonly used functions
 */
class CachedAdapter extends Flysystem\Cached\CachedAdapter
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param CacheInterface   $cache
     */
    public function __construct(AdapterInterface $adapter, Flysystem\Cached\CacheInterface $cache)
    {
        $this->adapter = $adapter;
        $this->cache = $cache;
        $this->cache->load();
    }

    /**
     * Get the underlying Adapter implementation.
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
    /**
     * Get the used Cache implementation.
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Flysystem\Config $config)
    {
        $result = $this->adapter->write($path, $contents, $config);
        if ($result !== \false) {
            $result['type'] = 'file';
            $this->cache->updateObject($path, $result + \compact('path', 'contents'), \true);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Flysystem\Config $config)
    {
        $result = $this->adapter->writeStream($path, $resource, $config);
        if ($result !== \false) {
            $result['type'] = 'file';
            $contents = \false;
            $this->cache->updateObject($path, $result + \compact('path', 'contents'), \true);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Flysystem\Config $config)
    {
        $result = $this->adapter->update($path, $contents, $config);
        if ($result !== \false) {
            $result['type'] = 'file';
            $this->cache->updateObject($path, $result + \compact('path', 'contents'), \true);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Flysystem\Config $config)
    {
        $result = $this->adapter->updateStream($path, $resource, $config);
        if ($result !== \false) {
            $result['type'] = 'file';
            $contents = \false;
            $this->cache->updateObject($path, $result + \compact('path', 'contents'), \true);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function rename($path, $newPath)
    {
        $result = $this->adapter->rename($path, $newPath);
        if ($result !== \false) {
            $this->cache->rename($path, $newPath);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $result = $this->adapter->copy($path, $newpath);
        if ($result !== \false) {
            $this->cache->copy($path, $newpath);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $result = $this->adapter->delete($path);
        if ($result !== \false) {
            $this->cache->delete($path);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $result = $this->adapter->deleteDir($dirname);
        if ($result !== \false) {
            $this->cache->deleteDir($dirname);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Flysystem\Config $config)
    {
        $result = $this->adapter->createDir($dirname, $config);
        if ($result !== \false) {
            $type = 'dir';
            $path = $dirname;
            $this->cache->updateObject($dirname, \compact('path', 'type'), \true);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $result = $this->adapter->setVisibility($path, $visibility);
        if ($result !== \false) {
            $this->cache->updateObject($path, \compact('path', 'visibility'), \true);
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $cacheHas = $this->cache->has($path);
        if ($cacheHas !== null) {
            return $cacheHas;
        }
        $adapterResponse = $this->adapter->has($path);
        if (!$adapterResponse) {
            $this->cache->storeMiss($path);
        } else {
            $cacheEntry = \is_array($adapterResponse) ? $adapterResponse : \compact('path');
            $this->cache->updateObject($path, $cacheEntry, \true);
        }

        return $adapterResponse;
    }

    /**
     * Get the path prefix.
     *
     * @return string|null path prefix or null if pathPrefix is empty
     */
    public function getPathPrefix()
    {
        return $this->adapter->getPathPrefix();
    }

    /**
     * Prefix a path.
     *
     * @param string $path
     *
     * @return string prefixed path
     */
    public function applyPathPrefix($path)
    {
        return $this->adapter->applyPathPrefix($path);
    }
    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = \false)
    {
        if ($this->cache->isComplete($directory, $recursive)) {
            return $this->cache->listContents($directory, $recursive);
        }
        $result = $this->adapter->listContents($directory, $recursive);
        if ($result !== \false) {
            $this->cache->storeContents($directory, $result, $recursive);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->adapter->readStream($path);
    }

    /**
     * Retrieve filesystem information for a given set of paths
     *
     * @param array $paths
     * @return array
     */
    public function eagerLoadPaths($paths)
    {
        if (!method_exists($this->getAdapter(), 'eagerLoadPaths')) {
            return [];
        }

        $cached = [];

        foreach ($paths as $key => $path) {
            $cacheHas = $this->cache->has($path);
            if ($cacheHas !== null) {
                $cached[$path] = $cacheHas;
                unset($paths[$key]);
            }
        }

        // Since we may have removed elements we need to reindex the array
        $paths = array_values($paths);

        $adapterResponse = $this->getAdapter()->eagerLoadPaths($paths);

        foreach ($paths as $path) {
            if (empty($adapterResponse) || empty($adapterResponse[$path])) {
                $this->cache->storeMiss($path);
            } else {
                $cacheEntry = \is_array($adapterResponse[$path]) ? $adapterResponse[$path] : \compact('path');
                $this->cache->updateObject($path, $cacheEntry, \true);
            }
        }

        return array_merge($cached, $adapterResponse);
    }

    /**
     * Call a method and cache the response.
     *
     * @param string $property
     * @param string $path
     * @param string $method
     *
     * @return mixed
     */
    protected function callWithFallback($property, $path, $method)
    {
        $result = $this->cache->{$method}($path);
        if ($result !== \false && ($property === null || \array_key_exists($property, $result))) {
            return $result;
        }
        $result = $this->adapter->{$method}($path);
        if ($result) {
            $object = $result + \compact('path');
            $this->cache->updateObject($path, $object, \true);
        }

        return $result;
    }
}
