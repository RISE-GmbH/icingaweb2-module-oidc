<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Oidc\Model;

use Icinga\Application\Config;
use Icinga\Data\Filter\FilterMatchNot;
use Icinga\Module\Oidc\Behavior\CustomBoolCast;
use Icinga\Module\Oidc\Behavior\SecondTimestamp;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Sql\Connection;

/**
 * A database model for User with the user table
 *
 */
class User extends DbModel
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
        return 'tbl_user';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumnDefinitions(): array
    {
        $backends = Config::app('authentication')->select()->addFilter(new FilterMatchNot('backend',"!=",'oidc'))->fetchAll();
        $backendOptions=[''=>t('Choose a backend...')];
        foreach ($backends as $name=>$backend){
            $backendOptions[$name]=$name;
        }
        return [
            'name'=>[
                'fieldtype'=>'text',
                'label'=>'Name',
                'description'=>t('A Name of something'),
                'required'=>true
            ],
            'provider_id'=>[
                'fieldtype'=>'select',
                'label'=>'Provider',
                'description'=>t('Provider'),
                'required'=>true,
                'multiOptions'=>(new Provider())->getAllAsArray('id','name')

            ],
            'email'=>[
                'fieldtype'=>'text',
                'label'=>'Email',
                'description'=>t('Email of the User'),
            ],
            'mapped_local_user'=>[
                'fieldtype'=>'text',
                'label'=>'mapped_local_user',
                'description'=>t('The local user this user is mapped to'),
            ],
            'mapped_backend'=>[
                'fieldtype'=>'select',
                'label'=>'mapped_backend',
                'description'=>t('The local backend this user is mapped to'),
                'multiOptions'=>$backendOptions,
            ],
            'active'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('Active'),
                'description'=>t('Enable or disable something'),
            ],
            'ctime'=>[
                'fieldtype'=>'localDateTime',
                'label'=>t('Created At'),
                'description'=>t('A Creation Time'),
            ]  ,
            'lastlogin'=>[
                'fieldtype'=>'localDateTime',
                'label'=>t('lastlogin At'),
                'description'=>t('A lastlogin Time'),
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

        $behaviors->add((new CustomBoolCast(['active']))->setStrict(false)->setFalseValue("0")->setTrueValue("1"));
        $behaviors->add(new SecondTimestamp(['mtime']));
        $behaviors->add(new SecondTimestamp(['ctime']));
        $behaviors->add(new MillisecondTimestamp(['lastlogin']));

    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('provider', Provider::class)->setForeignKey('id')->setCandidateKey('provider_id');

    }


}
