<?php

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseConfigTest extends TestCase
{
    public function test_pgsql_connection_disables_server_side_prepares_for_pgbouncer_pooler(): void
    {
        $options = config('database.connections.pgsql.options');
        $attr = PHP_VERSION_ID >= 80500
            ? \Pdo\Pgsql::ATTR_DISABLE_PREPARES
            : \PDO::PGSQL_ATTR_DISABLE_PREPARES;

        $this->assertIsArray($options);
        $this->assertArrayHasKey($attr, $options);
        $this->assertTrue($options[$attr]);
    }
}
