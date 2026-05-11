<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Oidc\Model;

use Icinga\Module\Oidc\Behavior\SecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Sql\Connection;

/**
 * A database model for Group with the group table
 *
 */
class Group extends DbModel
{

    public function beforeSave(Connection $db){
        if( isset($this->id) && $this->id !== null){
            $this->mtime = new \DateTime();
        }else{
            $this->ctime = new \DateTime();
        }
    }
    public function getTableName(): string
    {
        return 'tbl_group';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name'=>[
                'fieldtype'=>'text',
                'label'=>'Name',
                'description'=>t('A Name of a group'),
                'required'=>true,
                'readonly'=>'readonly'

            ],
            'provider_id'=>[
                'fieldtype'=>'select',
                'label'=>'Provider',
                'description'=>t('Provider'),
                'required'=>true,
                'multiOptions'=>(new Provider())->getAllAsArray('id','name'),
                'readonly'=>'readonly'
            ],

            'ctime'=>[
                'fieldtype'=>'localDateTime',
                'label'=>t('Created At'),
                'description'=>t('A Creation Time'),
            ]  ,
            'mtime'=>[
                'fieldtype'=>'localDateTime',
                'label'=>t('Modified At'),
                'description'=>t('A Modified Time'),
            ]
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {

        $behaviors->add(new SecondTimestamp(['mtime']));
        $behaviors->add(new SecondTimestamp(['ctime']));

    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('member', GroupMembership::class)->setForeignKey('group_id')->setCandidateKey('id');
        $relations->belongsTo('provider', Provider::class)->setForeignKey('id')->setCandidateKey('provider_id');

    }



}
