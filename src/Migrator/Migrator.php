<?php

namespace SilexStarter\Migrator;

class Migrator
{

    protected $migrations = [];

    public function migrate()
    {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    public function rollback()
    {
        foreach ($this->migrations as $migration) {
            $migration->down();
        }
    }

    public function addMigration(MigrationInterface $migration)
    {

    }
}
