<?php

namespace SilexStarter\Migration;

use SilexStarter\Contracts\MigrationInterface;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

abstract class Migration implements MigrationInterface
{
    protected $schema;

    public function __construct(SchemaBuilder $schema)
    {
        $this->schema = $schema;
    }
}
