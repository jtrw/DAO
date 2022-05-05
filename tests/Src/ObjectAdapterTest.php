<?php

namespace Jtrw\DAO\Tests\Src;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Exceptions\DatabaseException;
use Jtrw\DAO\Tests\DbConnector;
use Jtrw\DAO\ValueObject\ValueObjectInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ObjectAdapterTest extends TestCase
{
    private const TABLE_SETTINGS = "settings";
    
    private DataAccessObjectInterface $db;
    
    public function setUp(): void
    {
        $this->db = DbConnector::getInstance();
        parent::setUp(); // TODO: Change the autogenerated stub
    }
    
    public function testCurrentDate()
    {
        $sql = "SELECT CURRENT_DATE";
        $date = $this->db->select($sql, [], [], DataAccessObjectInterface::FETCH_ONE)->toNative();
        
        Assert::assertEquals($date, date("Y-m-d"));
    }
    
    public function testInsert()
    {
        $values = [
            'id_parent' => 0,
            'caption'   => 'test',
            'value'     => 'dataTest'
        ];
        $idSetting = $this->db->insert(static::TABLE_SETTINGS, $values);
        Assert::assertIsInt($idSetting);
        
        $sql = "SELECT * FROM settings";
        $search = [
            'id' => $idSetting
        ];
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        Assert::assertInstanceOf(ValueObjectInterface::class, $result);
        
        $resultData = $result->toNative();
        Assert::assertNotEmpty($resultData);
        Assert::assertEquals($values['value'], $resultData['value']);
    }
    
    public function testMassInsert()
    {
        $values = [
            [
                'id_parent' => 0,
                'caption'   => 'massTest1',
                'value'     => 'dataMassTest1'
            ],
            [
                'id_parent' => 0,
                'caption'   => 'massTest2',
                'value'     => 'dataMassTest2'
            ]
        
        ];
        $this->db->massInsert(static::TABLE_SETTINGS, $values);
        
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
        $search = [
            'caption' => $values[1]['caption']
        ];
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        Assert::assertInstanceOf(ValueObjectInterface::class, $result);
        
        $resultData = $result->toNative();
        Assert::assertNotEmpty($resultData);
        Assert::assertEquals($values[1]['value'], $resultData['value']);
    }
    
    public function testMassInsertInForeach()
    {
        $values = [
            [
                'id_parent' => 0,
                'caption'   => 'massTest3',
                'value'     => 'dataMassTest3'
            ],
            [
                'id_parent' => 0,
                'caption'   => 'massTest4',
                'value'     => 'dataMassTest4'
            ]
        
        ];
        $this->db->massInsert(static::TABLE_SETTINGS, $values, true);
        
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
        $search = [
            'caption' => $values[1]['caption']
        ];
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        Assert::assertInstanceOf(ValueObjectInterface::class, $result);
        
        $resultData = $result->toNative();
        Assert::assertNotEmpty($resultData);
        Assert::assertEquals($values[1]['value'], $resultData['value']);
    }
    
    public function testUpdate()
    {
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
        
        $result = $this->db->select($sql, [], [], DataAccessObjectInterface::FETCH_ALL);
        Assert::assertInstanceOf(ValueObjectInterface::class, $result);
        
        $resultData = $result->toNative();
        
        Assert::assertNotEmpty($resultData[0]);
        $currentValue = $resultData[0];
        
        $values = [
            'value' => "NewValueWithTimeStamp".time()
        ];
        
        $search = [
            'id' => $currentValue['id']
        ];
        
        $result = $this->db->update(static::TABLE_SETTINGS, $values, $search);
        Assert::assertIsInt($result);
        
        $sql = "SELECT * FROM settings";
        
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        Assert::assertInstanceOf(ValueObjectInterface::class, $result);
        
        $resultData = $result->toNative();
        Assert::assertNotEmpty($resultData);
        Assert::assertEquals($resultData['value'], $values['value']);
    }
    
    public function testDelete()
    {
        $values = [
            'id_parent' => 0,
            'caption'   => 'forDelete',
            'value'     => 'dataTest'
        ];
        $idSetting = $this->db->insert(static::TABLE_SETTINGS, $values);
        Assert::assertIsInt($idSetting);
        
        $countRows = $this->db->delete(static::TABLE_SETTINGS, ['id' => $idSetting]);
        
        Assert::assertEquals(1, $countRows);
        
        $search = [
            'id' => $idSetting
        ];
        
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
        
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        
        Assert::assertEmpty($result->toNative());
    }
    
    public function testAssoc()
    {
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
    
        $fetchAssocObject = $this->db->select($sql, [], [], DataAccessObjectInterface::FETCH_ASSOC);
        Assert::assertInstanceOf(ValueObjectInterface::class, $fetchAssocObject);
        
        $fetchAllObject = $this->db->select($sql, [], [], DataAccessObjectInterface::FETCH_ALL);
        Assert::assertInstanceOf(ValueObjectInterface::class, $fetchAllObject);
    
        $assocData = $fetchAssocObject->toNative();
        $allData = $fetchAllObject->toNative();
        Assert::assertNotEmpty($allData[0]);

        Assert::assertEquals($assocData[$allData[0]['id']]['value'], $allData[0]['value']);
    }
    
    public function testGetDataBaseType()
    {
        Assert::assertEquals(DbConnector::DRIVER_MYSQL, $this->db->getDatabaseType());
    }
    
    public function testGetTables()
    {
        Assert::assertSame(
            $this->db->getTables(),
            [
                "settings",
                "site_contents",
                "site_contents2settings"
            ]
        );
    }
    
    public function testSuccessTransactions()
    {
        $this->db->begin();
        
        $values = [
            'id_parent' => 0,
            'caption'   => 'TRANSACTION_BEGIN',
            'value'     => 'dataTest'
        ];
        $this->db->insert(static::TABLE_SETTINGS, $values);
    
        $values['caption'] = "TRANSACTION2_BEGIN";
    
        $idSetting = $this->db->insert(static::TABLE_SETTINGS, $values);
        
        $this->db->commit();
        
        Assert::assertNotEmpty($idSetting);
        $search = [
            'id' => $idSetting
        ];
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
        
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        $resultData = $result->toNative();
        Assert::assertNotEmpty($resultData);
        Assert::assertEquals($resultData['id'], $idSetting);
    }
    
    public function testRollbackTransactions()
    {
        $idSetting = 0;
        try {
            $this->db->begin();
    
            $values = [
                'id_parent' => 0,
                'caption'   => 'TRANSACTION_BEGIN',
                'value'     => 'dataTest'
            ];
            $idSetting = $this->db->insert(static::TABLE_SETTINGS, $values);
    
            $values = [
                'failed_field' => 0,
            ];
            $this->db->insert(static::TABLE_SETTINGS, $values);
            
            $this->db->commit();
        } catch (DatabaseException $exp) {
            $this->db->rollback();
        }
        
        Assert::assertNotEmpty($idSetting);
        $search = [
            'id' => $idSetting
        ];
        $sql = "SELECT * FROM ".static::TABLE_SETTINGS;
        
        $result = $this->db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ROW);
        Assert::assertEmpty($result->toNative());
    }
}