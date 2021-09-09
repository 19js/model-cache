<?php

declare(strict_types=1);
namespace Hpshop\ModelCache;

use Hpshop\ModelCache\Traits\ResourceTrait;
use Hyperf\DbConnection\Model\Model;

class BaseModel extends Model
{    
    protected $cache;
    use ResourceTrait;
    public function __construct()
    {
        $container = \Hyperf\Utils\ApplicationContext::getContainer();
        $this->cache=$container->get(\Psr\SimpleCache\CacheInterface::class);
    }
}