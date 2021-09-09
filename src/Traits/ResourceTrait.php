<?php
declare(strict_types=1);
namespace Hpshop\ModelCache\Traits;

trait ResourceTrait
{
    use CacheTrait;
    /**
     * 获取数据库表前缀
     *
     * @return string
     */
    public function getTablePrefix():string
    {
        $connectionName=$this->getConnectionName();
        return config('databases')[$connectionName]['prefix'];
    }
    /**
     * @param array $data
     * @return int
     */
    public function addData(array $data):int
    {
        $data=$this->addTime($data);
        $res =self::query()->insertGetId($data);
        $this->clearTag($this->getModelName());
        return $res;
    }
    public function createData(array $data)
    {
        $res = self::query()->create($data);
        $this->clearTag($this->getModelName());
        return $res;
    }
    public function insertData(array $data)
    {
        foreach ($data as &$vo)
        {
            if(is_array($vo)){
             $vo=$this->addTime($vo);
            }
        }
        $res = self::query()->insert($data);
        $this->clearTag($this->getModelName());
        return $res;
    }
    public function deleteDataByIds(array $id)
    {
        $res = self::destroy($id);
        $this->clearTag($this->getModelName());
        return $res;
    }
    public function deleteByWhere($where){
        $query=self::query();
        $this->parseWhere($where,$query);
        $res=$query->delete();
        $this->clearTag($this->getModelName());
        return $res;
    }
    public function editData(array $data,array $where):bool
    {
        $query=self::query();
        $data=$this->addTime($data,['update_at']);
        $this->parseWhere($where,$query);
        $res=$query->update($data);
        $this->clearTag($this->getModelName());
       return $res;
    }
    public function getCreateAtAttribute($val)
    {
        return $val > 0 ? date('Y-m-d H:i:s', $val) : $val;
    }
    public function getUpdateAtAttribute($val)
    {
        return $val > 0 ? date('Y-m-d H:i:s', $val) : $val;
    }
    /**
     * 获取多个
     * @param array $where
     * @param array $field
     * @param array $order
     * @param array $with
     * @return array
     */
    public function getDatas(array $where, array $field=['*'],array $order=[],array $with=[])
    {
        $cache_key=md5($this->getModelName().":getDatas:".json_encode($where).json_encode($field).json_encode($order).json_encode($with));
        $query=$this->query();
        return $this->modelCache($cache_key, function () use ($where,$query,$field,$order,$with)
        {
            $this->parseWhere($where,$query);
            $this->parseField($field,$query);
            $this->parseOrder($order,$query);
            $this->parseWith($with,$query);
            return $query->get();
        },
            [
                'tag'=>$this->getModelName()
            ]
        );
    }

    /**
     * 分页查找数据
     * @param array $where
     * @param array $field
     * @param array $order
     * @param array $with
     * @param int $page
     * @param int $per_page
     * @return mixed
     */
    public function getDatesByPage(array $where, array $field=['*'],array $order=[],array $with=[],$page=1, $per_page=10)
    {
        $cache_key=md5($this->getModelName().":getDatesByPage:".json_encode($where).json_encode($field).json_encode($order).json_encode($with).'_'.$page.'_'.$per_page);
        $query=$this->query();
        return $this->modelCache($cache_key, function () use ($where,$query,$field,$order,$with,$page,$per_page)
        {
            $this->parseWhere($where,$query);
            $this->parseOrder($order,$query);
            $this->parseWith($with,$query);
            return $query->paginate($per_page, $field, 'page',$page)->toArray();
        },
            [
                'tag'=>$this->getModelName()
            ]
        );
    }

    /**
     * 获取数据
     * @param array $where
     * @param array $field
     * @param array $order
     * @param array $with
     * @return
     */
    public function getData(array $where,array $field=[],array $order=[], array $with=[])
    {
        $cache_key=md5($this->getModelName().":getData".json_encode($where).json_encode($field).json_encode($order).json_encode($with));
        return $this->modelCache($cache_key,function($argv):array
        {
            $this->parseWhere($argv['where'], $argv['query']);
            $this->parseField($argv['field'],$argv['query']);
            $this->parseOrder($argv['order'],$argv['query']);
            $this->parseWith($argv['with'],$argv['query']);
            $res=$argv['query']->first();
            return $res;
        },['tag'=>$this->getModelName(), 'field'=>$field, 'where'=>$where, 'order'=>$order, 'with'=>$with, 'query' => $this->query()]
        );
    }
    /**
     * 获取Model名称
     * @return string
     */
    protected function getModelName():string
    {
        return ucfirst($this->table)."Model";
    }
    /**
     * 排序
     * @param array $order
     * @param [type] $query
     * @return void
     */
    protected function parseOrder(array $order,$query):void
    {
        if ($order)
        {
            foreach($order as $k=>$or){
                $query->orderBy($k,$or);
            }
        }
    }
    protected  function parseField(array $field,$query):void
    {
        if($field)
        {
            $query->select($field);
        }else{
            throw new \Exception('field is need');
        }
    }
    protected function parseWith(array $with,$query):void
    {
        if ($with)
        {
            foreach ($with as $vo)
            {
                $query->with($vo);
            }
        }
    }
    /**
     * where
     * @param array $wheres
     * @param [type] $query
     * @return void
     */
    protected function parseWhere(array $wheres,$query):void
    {
        foreach ($wheres as $k=>$where){
            if(is_array($where) && $where){
                $this->caseWhere($where,$query);
            }else{
                 $query->where($k,'=',$where);
            }

        }
    }

    /**
     * 处理where
     * @param [type] $where
     * @param [type] $query
     * @return void
     */
    protected function caseWhere($where,$query):void
    {
        switch(strtoupper($where[1]))
        {
            case 'IN':
                $query->whereIn($where[0],$where[2]);
            break;
            case 'BETWEEN':
                $query->whereBetween($where[0],$where[2]);
            break;
            default:
                $query->where($where[0],$where[1],$where[2]);
            break;
        }
    }

    /**
     * 新增时间
     * @param array $data
     * @param array $value
     * @return array
     */
    protected function addTime(array $data,array $value=['update_at','create_at']):array
    {
        $ddl=$this->getDDL();
        $time=time();
        foreach ($value as $vo){
            if($vo){
                if(in_array($vo,$ddl['field'])){
                    $data[$vo]=$time;
                }
            }
        }
        return $data;
    }
}