<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Database\Model;
use TheFramework\App\Database\Database;
use PHPUnit\Framework\MockObject\MockObject;

class ModelTest extends TestCase
{
    /** @var Database&MockObject */
    private $dbMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Database is a singleton, mock it to prevent real connection
        $this->dbMock = $this->createMock(Database::class);
        Database::setInstance($this->dbMock);
    }

    public function test_model_hydration()
    {
        $attributes = ['id' => 1, 'name' => 'John Doe'];
        $model = new UserMock();
        $model->setRawAttributes($attributes, true);
        $model->exists = true;
        
        $this->assertEquals(1, $model->id);
        $this->assertEquals('John Doe', $model->name);
        $this->assertTrue($model->exists);
    }

    public function test_fillable_attributes()
    {
        $attributes = [
            'id' => 1,           
            'name' => 'John',    
            'email' => 'test@test.com', 
            'secret' => '123'    
        ];
        
        $model = new UserMock();
        $model->fill($attributes);
        
        $this->assertEquals('John', $model->name);
        $this->assertNull($model->id);
        $this->assertNull($model->secret);
    }

    public function test_dirty_tracking()
    {
        $model = new UserMock();
        $model->setRawAttributes(['name' => 'Original'], true);
        
        $this->assertFalse($model->isDirty());
        
        $model->name = 'Changed';
        $this->assertTrue($model->isDirty());
        $this->assertTrue($model->isDirty('name'));
        $this->assertFalse($model->isDirty('email'));
        
        $this->assertEquals(['name' => 'Changed'], $model->getDirty());
    }

    public function test_soft_deletes()
    {
        $model = new UserMock();
        $model->setRawAttributes(['id' => 1, 'name' => 'John'], true);
        $model->exists = true;
        $model->setSoftDeletes(true);

        // Mock rowCount for the update call inside delete()
        $this->dbMock->method('rowCount')->willReturn(1);
        
        $model->delete();
        
        $this->assertTrue($model->trashed());
        $this->assertNotNull($model->deleted_at);
    }

    public function test_json_serialization()
    {
        $model = new UserMock();
        $model->setRawAttributes(['name' => 'John', 'password' => 'secret'], true);
        
        $json = json_encode($model);
        $data = json_decode($json, true);
        
        $this->assertEquals('John', $data['name']);
        $this->assertArrayNotHasKey('password', $data); 
    }

    public function test_boot_logic()
    {
        $this->assertTrue(UserMock::$booted_called);
    }
}

// Mock Model for testing
class UserMock extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email'];
    protected $hidden = ['password', 'secret'];
    public static $booted_called = false;

    public function setSoftDeletes(bool $value) {
        $this->softDeletes = $value;
    }

    protected static function booted(): void
    {
        static::$booted_called = true;
    }
}
