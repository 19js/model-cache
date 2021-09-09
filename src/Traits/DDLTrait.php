<?php
declare(strict_types=1);
namespace Hpshop\ModelCache\Traits;

use Hyperf\DbConnection\Db;

trait DDLTrait
{
    use ResourceTrait;
    /**
     * 获取数据库字段信息配置
     * @return array
     * @throws \Exception
     */
    public function getDDL():array
    {
        $prefix=$this->getTablePrefix();
        $table=$prefix.$this->getTable();
        $key='DDL:'.$table;
        return $this->modelCache($key,function() use ($table){
            $res= Db::select('SHOW FULL COLUMNS FROM `'.$table.'`');
            return $this->calDDL($res);
        },['tag'=>'DDL:Tables']);
    }
    /**
     * 处理输出data
     * @param array $data
     * @return array
     */
    protected function calDDL(array $data):array
    {
        $res=[];
        $resField=[];
        $fieldComment=[];
        foreach($data as $vo){
            $comment=$this->calComment($vo->Comment);
            $res[]=[
                'field' =>  $vo->Field,
                'name'  =>  $comment,
                'type'  =>  $vo->Type
            ];
            $fieldComment[$vo->Field]=$comment;
            $resField[]=$vo->Field;
        }
        return ['all'=>$res,'field'=>$resField,'fieldComment'=>$fieldComment];
    }
    protected function calComment(string $comment):array
    {
        $arr=[];
        $res=['origin_name'=>'','cal_name'=>'','status'=>[]];
        if($comment)
        {
            $arr=explode(' ',$comment);
        }
        if($arr){
            foreach ($arr as $k=>$a){
                if($k==0){
                    $res['origin_name']=$a;
                    $res['cal_name']=str_replace(['id'],'',$a);
                }else{
                    $status=$this->getStaus($a);
                    if(is_array($status)){
                        $res['status'][$status[0]]=$status[1];
                    }else{
                        $res['status'][]=$status;
                    }
                }
            }
        }
        return $res;
    }
    protected function getStaus(string $status)
    {
        $data=[];
        if($status){
            $a1=explode(':',$status);
            $a2=explode('：',$status);
            if(isset($a2[1]))
            {
                $a=$a2;
            }
            if(isset($a1[1])){
                $a=$a1;
            }
            if($a){
                $data=$a;
            }else{
                $data=$status;
            }
        }
        return $data;
    }
}