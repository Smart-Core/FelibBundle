<?php

declare(strict_types=1);

namespace SmartCore\Bundle\FelibBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DummyCacheProvider
{
    /** @var TagAwareCacheInterface */
    protected $pool;

    /**
     * CmsCacheProvider constructor.
     *
     * @param TagAwareCacheInterface $pool
     */
    public function __construct(TagAwareCacheInterface $pool = null)
    {
        $this->pool = $pool;
    }

    /**
     * @return TagAwareAdapter
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * Save cache data.
     *
     * @param string $key
     * @param mixed  $value
     * @param array  $tags
     * @param int|\DateInterval|null  $ttl
     */
    public function set(string $key, $value, array $tags = [], $ttl = null)
    {
        return new CacheItem();
    }

    /**
     * Get cache item value
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return null;
    }

    /**
     * @param string|array $keys
     *
     * @return bool
     */
    public function delete($keys): bool
    {
        return false;
    }

    /**
     * @param string $key
     */
    public function getItem(string $key)
    {
        return new CacheItem();
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getItemTags(string $key): array
    {
        return [];
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function invalidateTag(string $tag): bool
    {
        return false;
    }

    /**
     * @param array $tags
     *
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function clear()
    {
    }

    /**
     * @param $item
     */
    public function save($item)
    {
    }
}
