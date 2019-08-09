<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

class DecoratorManager
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;


    /**
     * @param DataProviderInterface $dataProvider
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(DataProviderInterface $dataProvider, CacheItemPoolInterface $cache, LoggerInterface $logger) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->dataProvider = $dataProvider;
    }


    /**
     * @param array $input
     * @return array
     * @throws Exception
     */
    public function getResponse(array $input): array {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
            $result = $this->dataProvider->get($input);
            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );
            $this->cache->save($cacheItem);
            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * @param array $input
     * @return string
     */
    public function getCacheKey(array $input) {
        return md5(json_encode($input));
    }
}
