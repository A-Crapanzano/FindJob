<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Enum\StatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[Route('/api', name: 'api_register')]
#[OA\Tag(name: 'Authentication')]
class RegisterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        description: 'Crée un nouveau compte utilisateur (candidat ou recruteur)',
        security: [],
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'password', 'firstname', 'lastname', 'status'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'newuser@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6, example: 'password123'),
                new OA\Property(property: 'firstname', type: 'string', example: 'Jane'),
                new OA\Property(property: 'lastname', type: 'string', example: 'Smith'),
                new OA\Property(property: 'city', type: 'string', example: 'Paris'),
                new OA\Property(property: 'zipcode', type: 'string', maxLength: 20, example: '75001'),
                new OA\Property(property: 'status', type: 'string', enum: ['candidate', 'recruiter'], example: 'candidate')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Inscription réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Inscription réussie'),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'newuser@example.com'),
                        new OA\Property(property: 'firstname', type: 'string', example: 'Jane'),
                        new OA\Property(property: 'lastname', type: 'string', example: 'Smith'),
                        new OA\Property(property: 'status', type: 'string', example: 'candidate')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrors')
    )]
    #[OA\Response(
        response: 409,
        description: 'Email déjà utilisé',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(
                    ['error' => 'Format JSON invalide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $constraints = new Assert\Collection([
                'email' => [
                    new Assert\NotBlank(message: 'L\'email est obligatoire'),
                    new Assert\Email(message: 'Format d\'email invalide'),
                ],
                'password' => [
                    new Assert\NotBlank(message: 'Le mot de passe est obligatoire'),
                    new Assert\Length(
                        min: 6,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                    ),
                ],
                'firstname' => new Assert\NotBlank(message: 'Le prénom est obligatoire'),
                'lastname' => new Assert\NotBlank(message: 'Le nom est obligatoire'),
                'city' => new Assert\Optional(),
                'zipcode' => new Assert\Optional([
                    new Assert\Length(max: 20)
                ]),
                'status' => [
                    new Assert\NotBlank(message: 'Le statut est obligatoire'),
                    new Assert\Choice(
                        choices: ['candidate', 'recruiter'],
                        message: 'Le statut doit être "candidate" ou "recruiter"'
                    )
                ],
            ]);

            $violations = $this->validator->validate($data, $constraints);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $data['email']]);

            if ($existingUser) {
                return $this->json(
                    ['error' => 'Cet email est déjà utilisé'],
                    Response::HTTP_CONFLICT
                );
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);

            $statusEnum = StatusEnum::tryFrom($data['status']);
            if ($statusEnum) {
                $user->setStatus($statusEnum);
            }

            if (isset($data['city'])) {
                $user->setCity($data['city']);
            }
            if (isset($data['zipcode'])) {
                $user->setZipcode($data['zipcode']);
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Inscription réussie',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'status' => $user->getStatus()->value,
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de l\'inscription'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
