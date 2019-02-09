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
                $this->loadDataOnRedis($scores, $cacheService);
            }
        } else {
            $scores = $scores->data;
        }

        return new JsonResponse((array) $scores);
    }

    public function getScoreByDateAction(Request $request) {
        $field = 'user_id';
        if ($request->attributes->get('_route') == 'get_score_by_store') {
            $field = 'store_id';
        }
        $searchParameters = json_decode($request->getContent());
        if (empty($searchParameters)) {
            return new JsonResponse(['status' => 'no search parameters'], 400);
        }
        $records = $this->findScoresFromMongodb(
            [
                $field => $searchParameters->id, 
                'date' => [
                    '$gte' => $searchParameters->date1,
                    '$lte' => $searchParameters->date2
                ],
                'status' => true
            ]
        );
        return new JsonResponse((array) $records);
    }

    public function getScoreByIdAction(Request $request) {
        $searchParameters = json_decode($request->getContent());
        if (empty($searchParameters)) {
            return new JsonResponse(['status' => 'no search parameters'], 400);
        }
        $records = $this->findScoresFromMongodb(
            ['_id' => $searchParameters->id]
        );
        return new JsonResponse((array) $records);
    }

    public function updateScoreAction(Request $request) {
        $searchParameters = json_decode($request->getContent());
        $fields = [
            'status' => false
        ];
        if ($this->getRequest()->isMethod('PUT')) {
            $fields = [
                'score' => $searchParameters->score,
                'date' => $searchParameters->date,
                'opinions' => $searchParameters->opinions
            ];
        }
        
        if (empty($searchParameters)) {
            return new JsonResponse(['status' => 'no search parameters'], 400);
        }
        try{
            $records = $this->updateScoresFromMongodb(
                $searchParameters->id,
                $fields
            );
        } catch (\Exception $e){
            return new JsonResponse(['status' => "Error updating"], 400);
        }
        $cacheService = $this->get('cache_service');
        $cacheService->updateRedis($searchParameters->id, $fields);
        return new JsonResponse(['status' => 'Record updated'], 200);
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
        $this->loadDataOnRedis([$score->_id => (array) $score], $cacheService);
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
                    $cacheService->set('scores:'.$score['_id'], $key, $value);
                }
            }
        }
    }

    /**
     * This method finds the scores on MongoDb
     * @return object list with the scores
     */
    private function findScoresFromMongodb($where = array(), $fields = array()) 
    {
        $database = $this->get('database_service')->getDatabase();
        $scores = $database->scores->find($where, $fields);
        $scores = iterator_to_array($scores);
        return $scores;
    }

    /**
     * This method update the scores on MongoDb
     * @return object list with the scores
     */
    private function updateScoresFromMongodb($id, $set = array()) 
    {
        $database = $this->get('database_service')->getDatabase();
        $database->scores->update(['_id' => $id], ['$set' => $set]);
    }
}
