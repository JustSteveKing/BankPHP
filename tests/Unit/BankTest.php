<?php

namespace JustSteveKing\Bank\Tests\Unit;

use PDO;
use JustSteveKing\Bank\Bank;
use JustSteveKing\Bank\Tests\TestCase;
use JustSteveKing\Bank\Exceptions\QueryException;
use JustSteveKing\Bank\Exceptions\TableNotDefined;

class BankTest extends TestCase
{
    protected $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Bank(
            [
            'host' => 'localhost',
            'database' => 'find',
            'username' => 'root',
            'password' => 'T3chR00lz',
            ]
        );
    }

    public function test_class_can_be_constructed()
    {
        $this->assertNotNull($this->database);
    }

    public function test_db_is_instance_of_pdo()
    {
        $this->assertInstanceOf(
            PDO::class,
            $this->database->getDb()
        );
    }

    public function test_table_can_be_selected()
    {
        $this->database->from($table = 'users');

        $this->assertEquals(
            $table,
            $this->database->getTable()
        );
    }

    public function test_all_fields_are_selected_by_default()
    {
        $this->database->from('users')->select();

        $this->assertEquals(
            '*',
            $this->database->getFields()
        );
    }

    public function test_select_fields_can_be_selected_as_array()
    {
        $fields = [
            'id',
            'name'
        ];

        $this->database->from('users')->select($fields);

        $this->assertEquals(
            implode(',', $fields),
            $this->database->getFields()
        );
    }

    public function test_select_fields_can_be_selected_as_a_string()
    {
        $fields = 'id,name';
        $this->database->from('users')->select($fields);

        $this->assertEquals(
            $fields,
            $this->database->getFields()
        );
    }

    public function test_can_get_a_result_from_table()
    {
        $result = $this->database->from('users')->select()->get();

        $this->assertNotNull($result);
    }

    public function test_exception_thrown_if_table_does_not_exist()
    {
        $this->expectException(QueryException::class);
        $this->database->from('fake_table')->select()->get();
    }

    public function test_exception_is_thrown_if_fields_do_not_exist()
    {
        $this->expectException(QueryException::class);
        $this->database->from('users')->select('fake_column')->get();
    }

    public function test_limit_can_be_set_on_query()
    {
        $results = $this->database->from('users')->limit(3)->select()->get();

        $this->assertEquals(3, count($results));
    }

    public function test_offset_can_be_set_on_query()
    {
        $results = $this->database->from('users')->limit(1)->offset(1)->select()->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals(2, $results[0]->id);
    }

    public function test_query_statistics_can_be_recorded()
    {
        $this->database->statsEnabled = true;
        $this->database->from('users')->select()->get();

        $this->assertArrayHasKey('queries', $stats = $this->database->getStats());
        $this->assertArrayHasKey('total_time', $stats);

        dump($stats);
        die();
    }

    public function test_multiple_queries_get_logged_in_statistics()
    {
        $this->database->statsEnabled = true;
        $this->database->from('users')->select()->get();
        $this->database->from('users')->select('id')->get();

        $this->assertArrayHasKey('queries', $stats = $this->database->getStats());
        $this->assertEquals(2, $stats['num_queries']);
        $this->assertEquals(2, count($stats['queries']));
    }

    protected function reset()
    {
        $this->database->reset();
    }
}
