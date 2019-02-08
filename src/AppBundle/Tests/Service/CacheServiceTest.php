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
    static $key = "customerTest";

    public function setUp() {
        $this->setCacheService(new cache('127.0.0.1', '6379', null));
    }

    public function testSet() {
        $muck = array
            (
            '59f93e22daddaac42600015a' => array
                (
                '_id' => array
                    (
                    '$id' => '59f93e22daddaac42600015a'
                ),
                'name' => 'leandro',
                'age' => 26
            ),
            '59f93e22daddaac42600015b' => array
                (
                '_id' => array
                    (
                    '$id' => '59f93e22daddaac42600015b'
                ),
                'name' => 'marcio',
                'age' => 30
            ),
            '59f93e22daddaac42600015c' => array
                (
                '_id' => array
                    (
                    '$id' => '59f93e22daddaac42600015c'
                ),
                'name' => 'deivid',
                'age' => 36
            )
        );
        
        foreach ($muck as $record) {
            $this->getCacheService()->set(self::$key, json_encode($record));
        }
        
        $this->assertEquals(3, $this->getCacheService()->getCache()->llen(self::$key));
    }
    
    public function testGet()
    {
        $data = $this->getCacheService()->get(self::$key);
        $record = $data->{'59f93e22daddaac42600015b'};
        $this->assertEquals('marcio', $record->name);
    }
    
    public function testDel()
    {
        $this->getCacheService()->del(self::$key);
        $data = $this->getCacheService()->get(self::$key);
        $this->assertNull($data);
    }
    
    private function setCacheService($cache) {
        $this->cacheService = $cache;
    }

    private function getCacheService() {
        return $this->cacheService;
    }
}
