<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Service\CacheService as cache;

class ScoresControllerTest extends WebTestCase
{
    protected $client;
    private $cacheService;

    private $mock = [ 
        'user_id' => 8000,
        'store_id' => 26,
        'buy_id' => 1,
        'score' => 1,
        'date' => "2019-02-10 00:00:00",
        'opinions' => "great test"
    ];

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        $this->setCacheService(new cache('127.0.0.1', '6379', null, 2, null));
        $this->client->request('DELETE', '/score/', [], [], ['CONTENT_TYPE' => 'application/json'], []);
    }

    public function testOkSaveScore()
    {
        $this->insertDate();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testWrongValidationScore()
    {
        $score = [ 
            'user_id' => 8000,
            'store_id' => 26,
        ];
        $score = json_encode($score);

        $this->client->request('POST', '/score/', [], [], ['CONTENT_TYPE' => 'application/json'], $score);
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateScore()
    {
        $this->insertDate();
        $score = [ 
            'id' => "8000261",
            'score' => 3,
            'date' => "2020-02-10 00:00:00",
            'opinions' => "OPINION CHANGED"
        ];
        $score = json_encode($score);
        $this->client->request('PUT', '/updatescore/', [], [], ['CONTENT_TYPE' => 'application/json'], $score);
        $this->assertEquals('{"status":"Record updated"}', $this->client->getResponse()->getContent());
    }

    public function testGetScoreByUser()
    {
        $this->insertDate();
        $score = [ 
            'id' => 8000,
            'date1' => "2018-01-01",
            'date2' => "2020-01-01"
        ];
        $expected = '{"8000261":{"_id":"8000261","user_id":8000,"store_id":26,"buy_id":1,"score":1,"date":"2019-02-10 00:00:00","opinions":"great test","status":true}}';
        $score = json_encode($score);
        $this->client->request('POST', '/scorebyuser/', [], [], ['CONTENT_TYPE' => 'application/json'], $score);
        $this->assertEquals($expected, $this->client->getResponse()->getContent());
    }

    public function testGetScoreByStore()
    {
        $this->insertDate();
        $score = [ 
            'id' => 26,
            'date1' => "2018-01-01",
            'date2' => "2020-01-01"
        ];
        $expected = '{"8000261":{"_id":"8000261","user_id":8000,"store_id":26,"buy_id":1,"score":1,"date":"2019-02-10 00:00:00","opinions":"great test","status":true}}';
        $score = json_encode($score);
        $this->client->request('POST', '/scorebystore/', [], [], ['CONTENT_TYPE' => 'application/json'], $score);
        $this->assertEquals($expected, $this->client->getResponse()->getContent());
    }

    public function testGetScoreById()
    {
        $this->insertDate();
        $score = [ 
            'id' => "8000261"
        ];
        $expected = '{"8000261":{"user_id":"8000","store_id":"26","buy_id":"1","score":"1","date":"2019-02-10 00:00:00","opinions":"great test","_id":"8000261","status":"1"}}';
        $score = json_encode($score);
        $this->client->request('POST', '/scorebyid/', [], [], ['CONTENT_TYPE' => 'application/json'], $score);
        $this->assertEquals($expected, $this->client->getResponse()->getContent());
    }

    private function setCacheService($cache) {
        $this->cacheService = $cache;
    }

    protected function tearDown()
    {
        $this->client->request('DELETE', '/score/', [], [], ['CONTENT_TYPE' => 'application/json'], []);
    }

    private function insertDate()
    {
        $score = json_encode($this->mock);
        $this->client->request('POST', '/score/', [], [], ['CONTENT_TYPE' => 'application/json'], $score);
    }
}
