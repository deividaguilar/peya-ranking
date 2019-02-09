<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ScoresController extends Controller {

    public function getAllScoresAction() {
        $cacheService = $this->get('cache_service');
        $key = 'scores';
        $scores = $cacheService->get($key);
        $online = $scores->online;
        if (empty($scores->data)) {
            $scores = $this->findScoresFromMongodb();
            if ($online == true) {
                var_dump($online, "cargo datos en redis");
                $this->loadDataOnRedis($scores, $cacheService);
            }
        } else {
            var_dump("datso redis");
            $scores = $scores->data;
        }

        return new JsonResponse($scores);
    }

    public function getScoreByUserIdAction() {
        $cacheService = $this->get('cache_service');
        $key = 'scores';
        $scores = $cacheService->get($key);
        $online = $scores->online;
        if (empty($scores->data)) {
            $scores = $this->findScoresFromMongodb();
            if ($online == true) {
                $this->loadDataOnRedis($scores, $cacheService);
            }
        } else {
            $scores = $scores->data;
        }

        return new JsonResponse($scores);
    }

    public function saveScoreAction(Request $request) {
        $score = json_decode($request->getContent());
        if (empty($score)) {
            return new JsonResponse(['status' => 'there is no score'], 400);
        }
        
        if (is_array($score)){
            return new JsonResponse(['status' => 'there is more one qualification'], 400);
        }

        $database = $this->get('database_service')->getDatabase();
        $score->_id = $score->user_id.$score->store_id.$score->buy_id;
        try {
            $database->scores->insert($score);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => "Score duplicate"], 400);
        }
        $cacheService = $this->get('cache_service');
        $this->loadDataOnRedis($this->findScoresFromMongodb(), $cacheService);
        return new JsonResponse(['status' => 'Score successfully created']);
    }

    public function deleteAllAction() 
    {
        $database = $this->get('database_service')->getDatabase();
        $database->scores->drop();
        $cacheService = $this->get('cache_service');
        $cacheService->del('scores');
        return new JsonResponse(['status' => 'Scores successfully deleted']);
    }

    /**
     * This method goes through the score array and load each one on a redis
     * List
     */

    private function loadDataOnRedis($scores, $cacheService) 
    {
        if ($cacheService->validateRedisConnection()) {
            foreach ($scores as $score) {
                foreach($score as $key => $value) {
                    $cacheService->set($score['user_id'], $key, $value);
                }
            }
        }
    }

    /**
     * This method finds the scores on MongoDb
     * @return object list with the scores
     */
    
    private function findScoresFromMongodb() 
    {
        $database = $this->get('database_service')->getDatabase();
        $scores = $database->scores->find();
        $scores = iterator_to_array($scores);
        return $scores;
    }
}
