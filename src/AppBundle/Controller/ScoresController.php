<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ScoresController extends Controller {

    public function getScoresAction(Request $request) {
        $id = array();
        if ($this->getRequest()->isMethod('POST')) {
            $searchParameters = json_decode($request->getContent());
            $validator = $this->get('validator_service');
            $resul = $validator->executeValidation(
                (array) $searchParameters,
                $request->attributes->get('_route')
            );

            if (!$resul) {
                return new JsonResponse(['status' => 'the parameters are incorrect '], 400);
            }
            $id = ['_id' => $searchParameters->id];
        }
        $cacheService = $this->get('cache_service');
        $key = 'scores';
        $scores = $cacheService->get($key, $id);
        return new JsonResponse((array) $scores->data);
    }

    public function getScoreByDateAction(Request $request) {
        $field = 'user_id';
        if ($request->attributes->get('_route') == 'get_score_by_store') {
            $field = 'store_id';
        }
        $searchParameters = json_decode($request->getContent());
        $validator = $this->get('validator_service');
        $resul = $validator->executeValidation(
            (array) $searchParameters,
            $request->attributes->get('_route')
        );

        if (!$resul) {
            return new JsonResponse(['status' => 'the parameters are incorrect '], 400);
        }
        $records = $this->get('database_service')->findScoresFromMongodb(
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

    public function updateScoreAction(Request $request) {
        $searchParameters = json_decode($request->getContent());
        $validator = $this->get('validator_service');
        $resul = $validator->executeValidation(
            (array) $searchParameters,
            $request->attributes->get('_route')
        );

        if (!$resul) {
            return new JsonResponse(['status' => 'the parameters are incorrect '], 400);
        }

        if (!is_numeric($searchParameters->score) || ($searchParameters->score < 1 || $searchParameters->score > 5)){
            return new JsonResponse(['status' => 'Value of score invalid.'], 400);
        }

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

        try{
            $records = $this->get('database_service')->updateScoresFromMongodb(
                $searchParameters->id,
                $fields
            );
        } catch (\Exception $e){
            return new JsonResponse(['status' => "Error updating"], 400);
        }
        $cacheService = $this->get('cache_service');
        $cacheService->updateRedis("scores", $searchParameters->id, $fields);
        return new JsonResponse(['status' => 'Record updated'], 200);
    }

    public function saveScoreAction(Request $request) {
        $score = json_decode($request->getContent()); 
        $validator = $this->get('validator_service');
        $resul = $validator->executeValidation(
            (array) $score,
            $request->attributes->get('_route')
        );

        if (!$resul) {
            return new JsonResponse(['status' => 'the parameters are incorrect '], 400);
        }
        
        if (is_array($score)){
            return new JsonResponse(['status' => 'there is more one qualification'], 400);
        }

        if (!is_numeric($score->score) || ($score->score < 1 || $score->score > 5)){
            return new JsonResponse(['status' => 'Value of score invalid.'], 400);
        }

        $database = $this->get('database_service')->getMongoDb();
        $score->_id = $score->user_id.$score->store_id.$score->buy_id;
        $score->status = true;
        try {
            $database->scores->insert($score);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => "Score duplicate"], 400);
        }
        
        $cacheService = $this->get('cache_service');
        $cacheService->loadDataOnRedis("scores",[$score->_id => (array) $score]);
        return new JsonResponse(['status' => 'Score successfully created']);
    }

    public function deleteAllAction() 
    {
        $database = $this->get('database_service')->getMongoDb();
        $database->scores->drop();
        $cacheService = $this->get('cache_service');
        $cacheService->del();
        return new JsonResponse(['status' => 'Scores successfully deleted']);
    }
}
