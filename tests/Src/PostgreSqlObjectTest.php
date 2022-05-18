<?php

namespace Jtrw\DAO\Tests\Src;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Exceptions\DatabaseException;
use Jtrw\DAO\Tests\DbConnector;
use PHPUnit\Framework\Assert;

class PostgreSqlObjectTest extends AbstractTestObjectAdapter
{
    public function setUp(): void
    {
        $this->db = DbConnector::getInstance(DbConnector::DRIVER_PGSQL);
        parent::setUp(); // TODO: Change the autogenerated stub
    }
    
    public function testGetDataBaseType()
    {
        Assert::assertEquals(DbConnector::DRIVER_PGSQL, $this->db->getDatabaseType());
    }
    
    public function testMatch()
    {
        Assert::assertTrue(true);
    }
    
    public function testDeleteTable()
    {
        $tableName = "test_".time();
        $sql = "CREATE TABLE {$tableName} (id serial NOT NULL)";
        $this->db->query($sql);
        
        $sqlSelect = "SELECT * FROM ".$tableName;
        
        $result = $this->db->select($sqlSelect, [], [], DataAccessObjectInterface::FETCH_ALL)->toNative();
        Assert::assertEmpty($result);
        
        $this->db->deleteTable($tableName);
        
        try {
            $this->db->select($sqlSelect, [], [], DataAccessObjectInterface::FETCH_ALL)->toNative();
            $this->fail('DatabaseException was not thrown');
        } catch (DatabaseException $exp) {
            $msg = sprintf("relation \"%s\" does not exist", $tableName);
            Assert::assertStringContainsString($msg, $exp->getMessage(), "Message Not Found");
        }
    }
    
    public function testGetTableIndexes()
    {
        $indexes = $this->db->getTableIndexes(static::TABLE_SETTINGS);
        Assert::assertNotEmpty($indexes[0]['table']);
        Assert::assertEquals($indexes[0]['table'], 'settings_pkey');
    }
}