<?php

namespace Icinga\Module\Oidc\Model;

use Icinga\Module\Oidc\Common\Database;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;

abstract class DbModel extends Model
{

    public function beforeSave(Connection $db){

    }

    public function getColumns(): array
    {
        return array_keys($this->getColumnDefinitions());
    }

    public function getValues()
    {
        $values=[];
        foreach ($this->getColumns() as $column) {
            if(isset($this->{$column})){
                $values[$column] = $this->{$column};
            }else{
                $values[$column] = null;
            }
        }
        return $values;
    }

    public function setValues($values)
    {
        foreach ($this->getColumns() as $column) {
            if (isset($values[$column])) {
                $this->{$column} = $values[$column];
            }else{
                $this->{$column} = null; // TODO scaffoldbuilder TODO other projects
            }

        }
    }

    public function save($asTransaction = true)
    {

        $db = Database::get();
        if($asTransaction){
            $db->beginTransaction();
        }
        $this->beforeSave($db);
        $behavior = new Behaviors();
        $this->createBehaviors($behavior);
        $behavior->persist($this);

        $values=$this->getValues();

        if (!isset ($this->id) || $this->id === null) {
            $db->insert($this->getTableName(), $values);
            $this->id = $db->lastInsertId();

        } else {
            $db->update($this->getTableName(), $values, [$this->getKeyName().' = ?' => $this->id]);
        }
        if($asTransaction){
            $db->commitTransaction();
        }
    }

    public function findbyPrimaryKey($id){
        $db = Database::get();
        return self::on($db)->filter(Filter::equal($this->getKeyName(), $id))->first();
    }

    public function findbyAttribute($attribute,$value){
        $db = Database::get();
        return self::on($db)->filter(Filter::equal($attribute, $value))->first();
    }

    public function getAllAsArray($key,$value){
        $db = Database::get();
        $result = [];
        $models =  self::on($db);
        foreach ($models as $model){
            if(isset($model->{$key})){
                if(isset($model->{$value})){
                    $result[$model->{$key}] = $model->{$value};
                }else{
                    $result[$model->{$key}] = null;
                }

            }

        }
        return $result;
    }

    public function delete()
    {
        $db = Database::get();
        $db->delete($this->getTableName(), [$this->getKeyName().' = ?' => $this->id]);
    }
}
