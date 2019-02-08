<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ScoresController extends Controller {

    /**
     * @Route("/score/")
     * @Method("GET")
     */
    public function getAction() {
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

    /**
     * @Route("/score/")
     * @Method("POST")
     */
    public function postAction(Request $request) {
        $score = json_decode($request->getContent());
        if (empty($score)) {
            return new JsonResponse(['status' => 'there is no score'], 400);
        }
        
        if (is_array($score)){
            return new JsonResponse(['status' => 'there is more one qualification'], 400);
        }

        $database = $this->get('database_service')->getDatabase();

        $database->scores->insert($score);
        $cacheService = $this->get('cache_service');
        $this->loadDataOnRedis($this->findScoresFromMongodb(), $cacheService);
        return new JsonResponse(['status' => 'Score successfully created']);
    }

    /**
     * @Route("/score/")
     * @Method("DELETE")
     */
    public function deleteAction() 
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
                $cacheService->set("scores", json_encode($score));
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
