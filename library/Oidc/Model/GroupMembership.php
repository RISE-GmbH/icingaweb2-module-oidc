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
class GroupMembership extends DbModel
{
    public function getTableName(): string
    {
        return 'tbl_group_membership';
    }
    public function beforeSave(Connection $db){
        if( isset($this->id) && $this->id !== null){
            $this->mtime = new \DateTime();
        }else{
            $this->ctime = new \DateTime();
        }
    }
    public function getKeyName()
    {
        return 'id';
    }

    public function getColumnDefinitions(): array
    {
        return [
            'username'=>[
                'fieldtype'=>'text',
                'label'=>'Username',
                'description'=>t('Name of a user'),
                'required'=>true
            ],
            'user_id'=>[
                'fieldtype'=>'text',
                'label'=>'User',
                'description'=>t('User'),
                'required'=>true
            ],
            'group_id'=>[
                'fieldtype'=>'text',
                'label'=>'Group',
                'description'=>t('Group'),
                'required'=>true
            ],
            'provider_id'=>[
                'fieldtype'=>'text',
                'label'=>'Provider',
                'description'=>t('Provider'),
                'required'=>true
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
        $relations->belongsTo('group', Group::class)->setForeignKey('id')->setCandidateKey('group_id');
        $relations->belongsTo('user', User::class)->setForeignKey('id')->setCandidateKey('user_id');
        $relations->belongsTo('provider', Provider::class)->setForeignKey('id')->setCandidateKey('provider_id');

    }



}
