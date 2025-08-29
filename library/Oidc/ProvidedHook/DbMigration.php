<?php

/* originally from  Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Oidc\ProvidedHook;

use DirectoryIterator;
use Icinga\Application\Hook\Common\DbMigrationStep;
use Icinga\Application\Hook\DbMigrationHook;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Module\Oidc\Common\Database;
use Icinga\Module\Oidc\Model\Schema;
use ipl\Orm\Query;
use ipl\Sql;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Adapter\Sqlite;
use ipl\Sql\Connection;
use SplFileInfo;

class DbMigration extends DbMigrationHook
{
    const SQLITE_UPGRADE_DIR = 'schema/sqlite-upgrades';

    public function getName(): string
    {
        return $this->translate('Icinga OpenID Connect Module');
    }

    public function providedDescriptions(): array
    {
        return [
            '0.5.5' => $this->translate(
                'Initial release with the create statements of the database in preparation of version 0.5.5'
            ),
            '0.5.6' => $this->translate(
                'Add group filter for groupsync, and a manual default group for each provider and blacklisted usernames'
            ),
            '0.5.7' => $this->translate(
                'Add required groups, which would disallow login if the user is not in one of these given groups'
            ),

        ];
    }
    public static function getColumnType(Connection $conn, string $table, string $column): ?string
    {
        if($conn->getAdapter() instanceof Sqlite){
            return null;
        }else{
            return parent::getColumnType($conn,$table,$column);
        }
    }
    public static function tableExists(Connection $conn, string $table): bool
    {

        if($conn->getAdapter() instanceof Sqlite){
            /** @var false|int $exists */
            $exists = $conn->prepexec(
                'SELECT name FROM sqlite_master WHERE type="table" and name = ?',
                $table
            )->fetchColumn();
            return $exists === $table;
        }else{
            return parent::tableExists($conn,$table);
        }

    }
    public function getVersion(): string
    {
        if ($this->version === null) {
            $conn = $this->getDb();
            if($conn->getAdapter() instanceof Sqlite){
                return "99.9.9";
            }
            $schema = $this->getSchemaQuery()
                ->columns(['version', 'success'])
                ->orderBy('id', SORT_DESC)
                ->limit(2);
            try{
                if (static::tableExists($conn, $schema->getModel()->getTableName())) {
                    /** @var Schema $version */

                    foreach ($schema as $version) {
                        if ($version->success) {
                            $this->version = $version->version;

                            break;
                        }
                    }

                    if (! $this->version) {
                        // Schema version table exist, but the user has probably deleted the entry!
                        $this->version = '0.0.0';
                    }

                } else {

                    $this->version = '0.0.0';
                }
            }catch (\Throwable $e){
                $this->version = '99.0.0';
            }

        }

        return $this->version;
    }

    public function getDb(): Sql\Connection
    {
        return Database::get();
    }

    protected function getSchemaQuery(): Query
    {
        return Schema::on($this->getDb());
    }
    protected function load(): void
    {
        if ($this->getDb()->getAdapter() instanceof Pgsql) {
            $upgradeDir = static::PGSQL_UPGRADE_DIR;
        }elseif($this->getDb()->getAdapter() instanceof Sqlite){
            $upgradeDir = static::SQLITE_UPGRADE_DIR;
        }else{
            $upgradeDir = static::MYSQL_UPGRADE_DIR;
        }

        if (! $this->isModule()) {
            $path = Icinga::app()->getBaseDir();
        } else {
            $path = Module::get($this->getModuleName())->getBaseDir();
        }

        $descriptions = $this->providedDescriptions();
        $version = $this->getVersion();
        /** @var SplFileInfo $file */
        foreach (new DirectoryIterator($path . DIRECTORY_SEPARATOR . $upgradeDir) as $file) {
            if (preg_match('/^(v)?([^_]+)(?:_(\w+))?\.sql$/', $file->getFilename(), $m, PREG_UNMATCHED_AS_NULL)) {
                [$_, $_, $migrateVersion, $description] = array_pad($m, 4, null);
                /** @var string $migrateVersion */
                if ($migrateVersion && version_compare($migrateVersion, $version, '>')) {
                    $migration = new DbMigrationStep($migrateVersion, $file->getRealPath());
                    if (isset($descriptions[$migrateVersion])) {
                        $migration->setDescription($descriptions[$migrateVersion]);
                    } elseif ($description) {
                        $migration->setDescription(str_replace('_', ' ', $description));
                    }

                    $migration->setLastState($this->loadLastState($migrateVersion));

                    $this->migrations[$migrateVersion] = $migration;
                }
            }
        }

        if ($this->migrations) {
            // Sort all the migrations by their version numbers in ascending order.
            uksort($this->migrations, function ($a, $b) {
                return version_compare($a, $b);
            });
        }
    }
}
