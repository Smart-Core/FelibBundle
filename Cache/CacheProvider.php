<?php

declare(strict_types=1);

namespace SmartCore\Bundle\FelibBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheProvider
{
    /** @var TagAwareCacheInterface */
    protected $pool;

    /**
     * CmsCacheProvider constructor.
     *
     * @param TagAwareCacheInterface $pool
     */
    public function __construct(TagAwareCacheInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return TagAwareAdapter
     */
    public function getPool(): TagAwareAdapter
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
        /** @var CacheItem $item */
        $item = $this->pool->getItem($key);
        $item->set($value);

        if (!empty($tags)) {
            $item->tag($tags);
        }

        if (!empty($ttl)) {
            $item->expiresAfter($ttl);
        }

        $this->pool->save($item);
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
        return $this->pool->getItem($key)->get();
    }

    /**
     * @param string|array $keys
     *
     * @return bool
     */
    public function delete($keys): bool
    {
        if (is_array($keys)) {
            return $this->pool->deleteItems($keys);
        }

        return $this->pool->deleteItem($keys);
    }

    /**
     * @param string $key
     */
    public function getItem(string $key)
    {
        return $this->pool->getItem($key);
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getItemTags(string $key): array
    {
        return $this->pool->getItem($key)->getMetadata();
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function invalidateTag(string $tag): bool
    {
        return $this->pool->invalidateTag($tag);
    }

    /**
     * @param array $tags
     *
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        return $this->pool->invalidateTags($tags);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * @param $item
     */
    public function save($item)
    {
        $this->pool->save($item);
    }
}
