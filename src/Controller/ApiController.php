<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{
    #[Route('/api', name: 'app_api')]
    public function index(): JsonResponse
    {
        return $this->json(['username' => 'apprenant1',
                            'email' => 'apprenant@yopmail.fr',
                            'lastname'=> 'Aprenant12',
                            'firstname' => 'apprenant',
                            'password'=>'$2y$10$6sUoOnLttEBOIUy81wUfFeKY/EGNjxGoTJrF3qdEzBmF58jJHrW8Wj',
                            'role'=>'apprenant']); 
    



         // Headers CORS nécessaires pour React Native
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
 
        return $response;
    }
}