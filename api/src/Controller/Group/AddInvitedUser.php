<?php
namespace App\Controller\Group;

use App\Controller\ApiController;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AddInvitedUser extends ApiController
{
    public function __construct(
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ) {
        parent::__construct($em, $serializer);
    }

    /**
     * @Route("/groups/invitation/{inviteKey}", methods={"POST"})
     */
    public function index(string $inviteKey): Response
    {
        $this->denyAccessUnlessGranted("ROLE_USER");

        $group = $this->em->getRepository(Group::class)->findOneBySecretKey($inviteKey);
        if (empty($group)) {
            return new JsonResponse(["error" => "Invalid invite key !"], Response::HTTP_BAD_REQUEST);
        }

        $group->addUser($this->getUser());
        $this->getUser()->addGroup($group);
        $this->em->persist($this->getUser());
        $this->em->persist($group);
        $this->em->flush();

        return new JsonResponse(["id" => $group->getId()], Response::HTTP_OK);
    }
}