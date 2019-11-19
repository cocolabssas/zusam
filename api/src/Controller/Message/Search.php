<?php

namespace App\Controller\Message;

use App\Entity\Group;
use App\Entity\Message;
use App\Controller\ApiController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class Search extends ApiController
{
    public function __construct(
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ) {
        parent::__construct($em, $serializer);
    }

    /**
     * @Route("/messages/search", methods={"POST"})
     */
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $requestData = json_decode($request->getcontent(), true);

        if (empty($requestData['search'])) {
            return new JsonResponse(['error' => 'You must provide search terms'], Response::HTTP_BAD_REQUEST);
        }
        $search_terms = explode(" ", $requestData['search']);

        // get the asked group
        if (empty($requestData['group'])) {
            return new JsonResponse(['error' => 'You must give a group id'], Response::HTTP_BAD_REQUEST);
        }
        $group = $this->em->getRepository(Group::class)->findOneById($requestData['group']);
        if (empty($group)) {
            return new JsonResponse(['error' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }
        $this->denyAccessUnlessGranted(new Expression('user in object.getUsersAsArray()'), $group);

        // which page of the results are we getting ?
        if (!empty($requestData['page'])) {
            $n = intval($requestData['page']);
        } else {
            $n = 0;
        }

        $query = $this->em->createQuery(
            "SELECT m FROM App\Entity\Message m"
            ." WHERE m.group = '".$group->getId()."'"
            .' ORDER BY m.id DESC'
        );
        //$query->setMaxResults(30);
        //$query->setFirstResult(30 * $n);
        $messages = $query->getResult();

        if (empty($messages)) {
            return new JsonResponse(['error' => 'No Message found'], Response::HTTP_NOT_FOUND);
        }

        $totalItems = 0;
        $page = [];
        $i = 0;
        foreach ($messages as $message) {
            $i++;
            if ($i < ($n + 1)*30) {
                continue;
            }
            $data = $message->getData();
            $score = 0;

            if (!empty($data["text"])) {
                foreach(explode(" ", $data["text"]) as $word) {
                    foreach ($search_terms as $term) {
                        if (stripos($word, $term) !== false) {
                            $score++;
                        }
                    }
                }
            }

            if (!empty($data["title"])) {
                foreach(explode(" ", $data["title"]) as $word) {
                    foreach ($search_terms as $term) {
                        if (stripos($word, $term) !== false) {
                            $score++;
                        }
                    }
                }
            }


            if ($score < 1) {
                continue;
            }

            $totalItems++;
            $previewId = $message->getPreview() ? $message->getPreview()->getId() : '';
            $authorId = $message->getAuthor() ? $message->getAuthor()->getId() : '';
            $parentId = $message->getParent() ? $message->getParent()->getId() : '';
            $page[] = [
                'id' => $message->getId(),
                'entityType' => $message->getEntityType(),
                'data' => $message->getData(),
                'author' => $authorId,
                'preview' => $previewId,
                'parent' => $parentId,
                'children' => count($message->getChildren()),
                'lastActivityDate' => $message->getLastActivityDate(),
                'score' => $score,
            ];
        }

        usort($page, function ($a, $b) {
            if ($a['score'] < $b['score']) {
                return 1;
            }
            if ($a['score'] > $b['score']) {
                return -1;
            }
            return $a['lastActivityDate'] < $b['lastActivityDate'];
        });

        // limit returned results
        $page = array_slice($page, 0, 100);

        $data = [
            'messages' => $page,
            'totalItems' => $totalItems,
        ];
        $response = new JsonResponse($data, JsonResponse::HTTP_OK);
        $response->setCache([
            'etag' => md5(json_encode($data)),
            'max_age' => 0,
            's_maxage' => 3600,
            'public' => true,
        ]);

        return $response;
    }
}
