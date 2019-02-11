<?php

namespace AppBundle\Service;

use \MongoClient;
use \MongoDB;

class DatabaseService
{
    private $database;

    public function __construct($host, $port, $database)
    {
        $mongoClient = new MongoClient("mongodb://$host:$port/");

        $this->setMongoDb(
            $mongoClient->selectDB($database)
        );
    }

    /**
     * This method finds the scores on MongoDb
     * @return object list with the scores
     */
    public function findScoresFromMongodb($where = array(), $fields = array()) 
    {
        $database = $this->getMongoDb();
        $scores = $database->scores->find($where, $fields);
        $scores = iterator_to_array($scores);
        return $scores;
    }

    /**
     * This method update the scores on MongoDb
     * @return object list with the scores
     */
    public function updateScoresFromMongodb($id, $set = array()) 
    {
        $database = $this->getMongoDb();
        $database->scores->update(['_id' => $id], ['$set' => $set]);
    }

    public function setMongoDb(MongoDB $database)
    {
        $this->database = $database;
    }

    public function getMongoDb()
    {
        return $this->database;
    }
}
