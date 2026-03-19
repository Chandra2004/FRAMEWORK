<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Database\Database;
use TheFramework\App\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock Database for QueryBuilder
        $this->db = $this->createMock(Database::class);
    }

    public function test_basic_select()
    {
        $qb = new QueryBuilder($this->db);
        $qb->table('users')->select(['id', 'name']);
        
        [$sql, $bindings] = $qb->toSql();
        
        $this->assertEquals('SELECT id, name FROM `users`', trim($sql));
    }

    public function test_where_clause()
    {
        $qb = new QueryBuilder($this->db);
        $qb->table('users')->where('id', 1)->where('status', 'active');
        
        [$sql, $bindings] = $qb->toSql();
        
        $this->assertStringContainsString('WHERE `users`.`id` = :where_0 AND `users`.`status` = :where_1', $sql);
        $this->assertEquals(1, $bindings[':where_0']);
        $this->assertEquals('active', $bindings[':where_1']);
    }

    public function test_or_where_clause()
    {
        $qb = new QueryBuilder($this->db);
        $qb->table('users')->where('id', 1)->orWhere('email', 'admin@test.com');
        
        [$sql, $bindings] = $qb->toSql();
        
        $this->assertStringContainsString('WHERE `users`.`id` = :where_0 OR `users`.`email` = :where_1', $sql);
    }

    public function test_where_in()
    {
        $qb = new QueryBuilder($this->db);
        $qb->table('users')->whereIn('id', [1, 2, 3]);
        
        [$sql, $bindings] = $qb->toSql();
        
        $this->assertStringContainsString('WHERE `users`.`id` IN (:in_0, :in_1, :in_2)', $sql);
        $this->assertEquals(1, $bindings[':in_0']);
    }

    public function test_joins()
    {
        $qb = new QueryBuilder($this->db);
        $qb->table('users')
           ->join('posts', 'users.id', '=', 'posts.user_id')
           ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id');
        
        [$sql, $bindings] = $qb->toSql();
        
        $this->assertStringContainsString('INNER JOIN `posts` ON users.id = posts.user_id', $sql);
        $this->assertStringContainsString('LEFT JOIN `profiles` ON users.id = profiles.user_id', $sql);
    }

    public function test_order_and_limit()
    {
        $qb = new QueryBuilder($this->db);
        $qb->table('users')->orderBy('id', 'DESC')->limit(10)->offset(5);
        
        [$sql, $bindings] = $qb->toSql();
        
        $this->assertStringContainsString('ORDER BY `users`.`id` DESC', $sql);
        $this->assertStringContainsString('LIMIT :main_limit', $sql);
        $this->assertStringContainsString('OFFSET :main_offset', $sql);
        $this->assertEquals(10, $bindings[':main_limit']);
        $this->assertEquals(5, $bindings[':main_offset']);
    }

    public function test_nested_where()
    {
        $qb = new QueryBuilder($this->db);
        
        $model = $this->createMock(\TheFramework\App\Database\Model::class);
        $model->method('newQueryWithoutScopes')->willReturnCallback(function() {
            $q = new QueryBuilder($this->db);
            $q->table('users'); // Ensure table is set for nested query too
            return $q;
        });
        $model->method('getTable')->willReturn('users');

        $qb->setModel($model);
        $qb->where('active', 1)
           ->where(function($query) {
               $query->where('role', 'admin')->orWhere('role', 'editor');
           });

        [$sql, $bindings] = $qb->toSql();
        
        $this->assertStringContainsString('WHERE `users`.`active` = :where_0 AND (`users`.`role` = :where_0 OR `users`.`role` = :where_1)', $sql);
    }
}
