<?php

declare(strict_types=1);
namespace Hpshop\ModelCache\Traits;

use Hyperf\Contract\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

trait CacheTrait{
    /**
     * 清理DDL数据
     * @param string $table 传入清理对应的表，不填清理全部
     * @return void
     */
    public function clearDDL($table="")
    {
        if($table){
            $this->cache->delete("DDL:".$table);
        }else{
            $this->clearTag('DDL:Tables');
        }
    }
    protected function modelCache(string $cache_key, $fun, $args):array
    {
        $data=[];
        $cacheData = $this->getCache($cache_key);
        if($cacheData){
            return $cacheData;
        }
        $data = $fun($args);
        $this->tag($args['tag'], $cache_key);
        $this->setCache($cache_key, $data);
        return $data;
    }

    protected function getCache(string $cache)
    {
        return  $this->cache->get($cache);
    }

    protected function setCache(string $cache, array $data)
    {
        return $this->cache->set($cache, $data);
    }

    /**
     * 设置tag.
     * @param $name
     * @param $value
     * @return bool
     */
    protected function tag($name, $value)
    {
        $cacheArray = [];
        $name = $this->getTagName($name);
        if ($this->cache->get($name)) {
            $cacheArray = $this->cache->get($name);
        }
        if (! in_array($value, $cacheArray)) {
            array_push($cacheArray, $value);
            $this->cache->set($name, $cacheArray);
        }
        return true;
    }

    /**
     * 清理缓存.
     * @param $name
     * @return bool
     */
    public function clearTag($name)
    {
        $name = $this->getTagName($name);
        if ($this->cache->has($name)) {
            $cacheArray = $this->cache->get($name);
            $res = true;
            if ($cacheArray) {
                $res = $this->cache->deleteMultiple($cacheArray);
            }
            if ($res) {
                $this->cache->delete($name);
            }
        }
        return true;
    }
    protected function getTagName($name)
    {
        return 'HpShop:Model:Tag:' . $name;
    }

}