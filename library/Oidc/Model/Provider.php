<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Oidc\Model;

use Icinga\Application\Config;
use Icinga\Application\Modules\Module;
use Icinga\Module\Oidc\Common\Database;
use Icinga\Module\Oidc\FileHelper;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Sql\Connection;

/**
 * A database model for Provider with the provider table
 *
 */
class Provider extends DbModel
{
    public function getTableName(): string
    {
        return 'tbl_provider';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumnDefinitions(): array
    {
        $fileHelper = new FileHelper(Module::get('oidc')->getConfigDir().DIRECTORY_SEPARATOR."files");
        return [
            'name'=>[
                'fieldtype'=>'text',
                'label'=>'Name',
                'description'=>t('A Name of the provider'),
                'required'=>true
            ],
            'url'=>[
                'fieldtype'=>'text',
                'label'=>'Url',
                'description'=>t('Url to redirect to the provider'),
                'required'=>true
            ],
            'secret'=>[
                'fieldtype'=>'password',
                'label'=>'Secret',
                'description'=>t('Shared secret for the provider'),
                'required'=>true
            ],
            'appname'=>[
                'fieldtype'=>'text',
                'label'=>'Appname',
                'description'=>t('Appname for the provider'),
                'required'=>true
            ],
            'syncgroups'=>[
                'fieldtype'=>'text',
                'label'=>'Groups to sync',
                'description'=>t('A comma seperated list of groups to sync for example "grp-icinga-admin*, grp-icinga-user*"'),
                'required'=>true,
                'value'=>'*'
            ],
            'defaultgroup'=>[
                'fieldtype'=>'text',
                'label'=>'Defaultgroup',
                'description'=>t('If this is set each user will get this particular group for example as a baseline of permissions'),
            ],
            'required_groups'=>[
                'fieldtype'=>'text',
                'label'=>'Required Groups',
                'description'=>t('If this is set each user will need to be in one of these groups to be able to login, for example "icinga-login, ubuntu-admin", leave empty if you do not need this.'),
            ],
            'usernameblacklist'=>[
                'fieldtype'=>'text',
                'label'=>'Username Blacklist',
                'description'=>t('A comma seperated list of usernames that are not allowed to login via oidc, for example "admin, admin-*, root'),
            ],
            'logo'=>[
                'fieldtype'=>'select',
                'label'=>t('Logo'),
                'multiOptions'=>$fileHelper->filelistAsSelect(),
                'description'=>t('Choose on of your previously uploaded logos')
            ],
            'buttoncolor'=>[
                'fieldtype'=>'color',
                'label'=>t('Button Color'),
                'description'=>t('Color of this OIDC button'),
                'required'=>true
            ],
            'textcolor'=>[
                'fieldtype'=>'color',
                'label'=>t('Text Color'),
                'description'=>t('Text Color of this OIDC button'),
                'required'=>true
            ],
            'caption'=>[
                'fieldtype'=>'text',
                'label'=>'Caption',
                'description'=>t('Caption for the provider'),
                'required'=>true
            ],
            'nooidcgroups'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('No OIDC Groups'),
                'description'=>t('Enable this to prevent fetching any groups from the OIDC provider'),
            ],
            'enabled'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('Enabled'),
                'description'=>t('Enable or disable this provider'),
            ],
            'enforce_scheme_https'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('Enforce Https on redirect urls'),
                'description'=>t('This option is necessary if you run IcingaWeb2 behind a reverse proxy, since the scheme (https) cannot be detected correctly'),
            ],
            'ctime'=>[
                'fieldtype'=>t('localDateTime'),
                'label'=>t('Created At'),
                'description'=>t('A creation time'),
            ]  ,
            'mtime'=>[
                'fieldtype'=>t('localDateTime'),
                'label'=>t('Modified At'),
                'description'=>t('A modification time'),
            ]
        ];
    }
    public function beforeSave(Connection $db){


        if( isset($this->id) && $this->id !== null){
            $this->mtime = new \DateTime();
            $old = (new Provider())->findbyPrimaryKey($this->id);

            foreach ($this->getColumnDefinitions() as $column=>$properties){
                if(is_array($properties) && isset($properties['fieldtype']) && $properties['fieldtype']=== 'password'){

                    if(strpos($this->{$column},'_ipl_form_') === 0){
                        $this->{$column} = $old->{$column};

                    }
                }
            }
        }else{
            $this->ctime = new \DateTime();
        }
    }


    public function save($asTransaction = true)
    {
        parent::save($asTransaction);
        $this->syncBackends();
    }
    public function getUserBackendName(){
        $configUserBackends = Config::app('authentication');
        $userBackends = $configUserBackends->select()->where('backend','oidc')->where('provider_id',$this->id)->fetchAll();
        foreach ($userBackends as $name =>$backend){
            return $name;
        }
        return null;
    }
    public function getUserGroupBackendName(){
        $configUserGroupBackends = Config::app('groups');
        $userGroupBackends = $configUserGroupBackends->select()->where('backend','oidc')->where('provider_id',$this->id)->fetchAll();
        foreach ($userGroupBackends as $name =>$backend){
            return $name;
        }
        return null;
    }
    public function syncBackends(){

        $configUserBackends = Config::app('authentication');
        $configUserGroupBackends = Config::app('groups');

        $userBackends = $configUserBackends->select()->where('backend','oidc');
        $userGroupBackends = $configUserGroupBackends->select()->where('backend','oidc');

        foreach($userBackends as $name=>$backend){
            $configUserBackends->removeSection($name);
        }
        foreach($userGroupBackends as $name=>$backend){
            $configUserGroupBackends->removeSection($name);
        }

        foreach( Provider::on(Database::get()) as $provider){
            $configUserBackends->setSection($provider->name,['backend'=>'oidc', 'provider_id'=>$provider->id, 'disabled'=>'1']);
            $configUserGroupBackends->setSection($provider->name,['backend'=>'oidc', 'provider_id'=>$provider->id, 'disabled'=>'1']);

        }

        $configUserBackends->saveIni();
        $configUserGroupBackends->saveIni();
    }
    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add((new BoolCast(['enabled'])));
        $behaviors->add((new BoolCast(['nooidcgroups'])));
        $behaviors->add(new MillisecondTimestamp(['mtime']));
        $behaviors->add(new MillisecondTimestamp(['ctime']));
    }
    public function createRelations(Relations $relations)
    {
        $relations->hasMany('group', Group::class)->setForeignKey('provider_id')->setCandidateKey('id');
        $relations->hasMany('user', User::class)->setForeignKey('provider_id')->setCandidateKey('id');
    }


}
