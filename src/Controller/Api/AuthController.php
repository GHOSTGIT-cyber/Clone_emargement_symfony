<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    #[Route('/login/test', name: 'login_test', methods: ['GET'])]
    public function testLogin(): JsonResponse
    {
        return $this->json([
            'message' => 'API de connexion fonctionnelle (test)',
            'note' => 'POST /api/login est géré automatiquement par le bundle JWT.'
        ]);
    }
}