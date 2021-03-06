<?php

namespace AppBundle\Service;

use Predis;

//   AppBundle\Service\DatabaseService;

/**
 * this class implements a CacheService.
 * It contain a failover, which means that if you cannot retrieve
 * data, it has to hit the Database.
 * */
class CacheService {

    /**
     * This property is load with Redis connection.
     */
    private $cache;
    /**
     *
     * @var database_service 
     */
    private $database;

    public function __construct($host, $port, $prefix, $cacheDb, $database) 
    {
        $parameters = array(
            'scheme' => 'redis',
            'host' => $host,
            'port' => $port,
            'database' => $cacheDb
        );
        $client = new Predis\Client($parameters);
        $this->setCache($client);
        $this->setDataBase($database);
    }

    /**
     * This method gets all redis data depending of key, in this case it'll be 
     * "scores".
     * @return JSON structure list with the scores 
     * @param string $key with key name.
     */
    public function get($key, $id)
    {
        $return = array();
        if ($this->validateRedisConnection()) {
            $this->validateLogCacheOffLine($key);
            $redis = $this->getCache();
            $id = (empty($id)) ? '*' : $id['_id'];
            $keysRedis = $redis->keys("{$key}:{$id}");
            $return['data'] = []; 
            foreach ($keysRedis as $value) {
                list($prf, $id) = explode(":", $value);
                $return['data'][$id] = $redis->hgetall($value);
            }
        } else {
            $return['data'] = $this->getDataBase()->findScoresFromMongodb($id);
        }
        return (object) $return;
    }

    public function updateRedis($keyName, $id, $values){
        foreach ($values as $key => $value) {
            $this->set("{$keyName}:{$id}", $key, $value);
        }
    }

    /**
     * This method load a record on Redis List
     * @param string $key with key name.
     * @param string $value with the json string of scores.
     */
    
    public function set($key, $key2, $value) 
    {
        if ($this->validateRedisConnection()) {
            $this->getCache()->hset($key, $key2, $value);
        }
    }

    /**
     * This method deletes all records belong to "scores" key on redis.
     * @param string $key with key name
     */
    public function del() 
    {
        if ($this->validateRedisConnection()) {
            $this->getCache()->flushDb();
        }
    }

    /**
     * Set $cache property
     * @param connnection redis
     */
    private function setCache($cache) 
    {
        $this->cache = $cache;
    }

    /**
     * Get $cache property
     * @return connection redis
     */
    
    /**
     * 
     * @return redis object to handle connection with redis.
     */
    public function getCache() 
    {
        return $this->cache;
    }

    /**
     * This method builds with the redis data the same structure than Mongo db
     * @param array $data with scores.
     * @return list object with scores.
     */
    private function decodeData($data) 
    {
        $output = new \stdClass();
        foreach ($data as $record) {
            $dataDecode = json_decode($record);
            $output->{$this->catchId($dataDecode)} = $dataDecode;
        }
        return $output;
    }

    /**
     * This method goes through json structure to find the mongo id for each one
     * record
     * @param json structure $dataJson
     * @return string $id it's a mongo Id
     */
    private function catchId($dataJson) 
    {
        foreach ($dataJson as $values) {
            foreach ($values as $key => $id) {
                if ($key == (string) '$id') {
                    return $id;
                }
            }
        }
    }

    /**
     * This method validates if the redis connection is enable. In true case 
     * returns true on the contrary returns false.
     * @return boolean 
     */
    
    public function validateRedisConnection() 
    {
        try {
            $this->cache->ping();
            return true;
        } catch (\Predis\Connection\ConnectionException $ex) {
            $this->createlogOffLine();
            return false;
        }
    }
    
    /**
     * 
     * @param mongoDb object
     */
    
    private function setDataBase($dataBase) 
    {
        $this->database = $dataBase;
    }

    /**
     * 
     * @return mongoDb object
     */
    
    private function getDataBase() 
    {
        return $this->database;
    }

    /**
     * This method validates if there are active states "offline". If it's true,
     * the cache memory is delete by $key.
     * @param string $key
     */
    
    private function validateLogCacheOffLine($key) 
    {
        $state = $this->getDataBase()->getMongoDb()->logoffline->count(array(
            'state' => true
        ));

        if ($state > 0) {
            $this->updateLogOffLine();
            $this->del();
            $records = $this->getDataBase()->findScoresFromMongodb();
            $this->loadDataOnRedis($key, $records);
        } 
    }

    /**
     * This method creates a record everytime that connection with redis is
     * off line.
     */
    private function createlogOffLine() 
    {
        $logOffline = array(
            'date_time' => date('Y-m-d H:i:s'),
            'state' => true
        );
        $this->getDataBase()->getMongoDb()->logoffline->insert($logOffline);
    }

    /**
     * This method updates the records when redis service is on line. These records
     * are changed to false state.
     */
    private function updateLogOffLine() 
    {
        $this->getDataBase()->getMongoDB()->logoffline->update(
            array(
                'state' => true
            ), 
            array(
                '$set' => 
                    array(
                        "state" => false
                    )
                ),
            array(
                'multiple' => true
            )
        );
    }

    /**
     * This method goes through the score array and load each one on a redis
     * List
     */
    public function loadDataOnRedis($keyName, $scores) 
    {
        foreach ($scores as $score) {
            foreach($score as $key => $value) {
                $this->set("{$keyName}:{$score['_id']}", $key, $value);
            }
        }
    }
}