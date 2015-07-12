<?php

namespace SilexStarter\Contracts;

use Illuminate\Database\Schema\Builder as SchemaBuilder;

interface MigrationInterface
{
    public function __construct(SchemaBuilder $schema);

    /**
     * Upgrade the database table structure with new structure.
     */
    public function up();

    /**
     * Downgrade the table structure into previous one.
     */
    public function down();
}
