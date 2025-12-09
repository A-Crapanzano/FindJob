<?php

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Authentication')]
class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Connexion utilisateur',
        description: 'Authentifie un utilisateur avec email et mot de passe',
        security: [],
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Connexion réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Connexion réussie'),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                        new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                        new OA\Property(property: 'lastname', type: 'string', example: 'Doe')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Identifiants invalides',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Identifiants invalides')
            ]
        )
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Identifiants invalides'
            ], 401);
        }

        return $this->json([
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ]
        ]);
    }
}
