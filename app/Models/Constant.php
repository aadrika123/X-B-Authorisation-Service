<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Constant extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $_ALL_CONSTANT;
    public $_REDIS_KEY = 'ALL-CONSTANT';

    public function store($data)
    {
        $id =  self::create($data)->id;
        $this->redisData();
        return $id;
    }

    public function edit($id,$data)
    {
        $update = self::where("id",$id)->update($data);
        $this->redisData();
        return $update;
    }

    public function delRedisData()
    {
        Redis::del($this->_REDIS_KEY);
    }

    public function setRedisData()
    {
        Redis::set($this->_REDIS_KEY, $this->_ALL_CONSTANT);
    }
    public function getRedisData()
    {
        // $this->_ALL_CONSTANT = json_decode(Redis::get($this->_REDIS_KEY), true) ?? null; 
        if(!$this->_ALL_CONSTANT)
        {
           $this->redisData();
        }
        return collect($this->_ALL_CONSTANT)->map(function($val){            
            $values = $val["const_values"];
            if(in_array(strtoupper($val["data_types"]),["INT","INTEGER","FLOAT","STRING","BOOL","BOOLEAN"]))
                settype($values,$val["data_types"]);
            $val["convert_values"] = $values;
            return $val;
        });
    }

    private function redisData()
    {
        if (!$this->_ALL_CONSTANT) 
        {
            $this->_ALL_CONSTANT = $this->getAllActiveConstant();
            $this->delRedisData();
            $this->setRedisData();
            $this->_ALL_CONSTANT = $this->getRedisData();
        }
    }

    public function getAllActiveConstant()
    {
        return self::where("status",1)->get();
    }

    public function getConstantById($id)
    {
        return $this->getRedisData()->where("id",$id)->first();
    }

    public function getConnectionByName($name)
    {
        return $this->getRedisData()->where("const_name",$name);
    }
}
