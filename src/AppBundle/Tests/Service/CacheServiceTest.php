<?php

namespace AppBundle\Tests\Service;

use AppBundle\Service\CacheService as cache;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CacheServiceTest
 *
 * @author Deivid
 */
class CacheServiceTest extends \PHPUnit_Framework_TestCase {

    private $cacheService;
    static $key = "scoreTest";

    public function setUp() {
        $this->setCacheService(new cache('127.0.0.1', '6379', null, 2, null));
        $this->getCacheService()->del();
    }

    public function testSet() {
        $this->insertData();
        $this->assertEquals(3, count($this->getCacheService()->getCache()->keys(self::$key."*")));
    }
    
    public function testGet()
    {
        $this->insertData();
        $data = $this->getCacheService()->getCache()->hgetall(self::$key.":80054382261");
        $this->assertEquals("today is the day", $data['opinions']);
    }
    
    public function testUpdate()
    {
        $this->insertData();
        $data = $this->getCacheService()->updateRedis(self::$key, "80054382261", ["status" => false]);
        $data = $this->getCacheService()->getCache()->hgetall(self::$key.":80054382261");
        $this->assertEmpty($data['status']);
    }
    
    public function testDel()
    {
        $this->insertData();
        $this->getCacheService()->del();
        $this->assertEquals(0, count($this->getCacheService()->getCache()->keys(self::$key."*")));
    }

    private function setCacheService($cache)
    {
        $this->cacheService = $cache;
    }

    private function getCacheService()
    {
        return $this->cacheService;
    }

    private function insertData()
    {
        $mock = [
            "80054382261" => [
              "_id" => "80054382261",
              "user_id" => "80054382",
              "store_id" => "26",
              "buy_id" => "1",
              "score" => "4",
              "date" => "2020-01-01",
              "opinions" => "today is the day",
              "status" => "1"
            ],
            "80054382263" => [
              "_id" => "80054382263",
              "user_id" => "80054382",
              "store_id" => "26",
              "buy_id" => "3",
              "score" => "4",
              "date" => "2020-01-01",
              "opinions" => "today is the day",
              "status" => "1"
            ],
            "80054382262" => [
              "_id" => "80054382262",
              "user_id" => "80054382",
              "store_id" => "26",
              "buy_id" => "2",
              "score" => "4",
              "date" => "2020-01-01",
              "opinions" => "today is the day",
              "status" => "1"
            ]
        ];
        
        foreach ($mock as $record) {
            foreach($record as $field => $value){
                $this->getCacheService()->set(self::$key.":".$record['_id'], $field, $value);
            }
        }
    }

    protected function tearDown()
    {
        $this->getCacheService()->del();
    }
}
