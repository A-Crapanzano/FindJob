<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
class UserController extends AbstractController
{
    #[Route('/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(#[CurrentUser] User $user): JsonResponse
    {
        $company = $user->getCompany();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'city' => $user->getCity(),
            'zipcode' => $user->getZipcode(),
            'status' => $user->getStatus()->value,
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'company' => $company ? [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'description' => $company->getDescription(),
                'location' => $company->getLocation(),
                'size' => $company->getSize()?->value,
                'website' => $company->getWebsite(),
                'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
            ] : null,
        ], 200);
    }

    #[Route('/user/{id}', name: 'api_user_detail', methods: ['GET'])]
    public function getOneUser(UserRepository $repository, int $id): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'city' => $user->getCity(),
            'zipcode' => $user->getZipcode(),
            'status' => $user->getStatus(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 200);
    }

    #[Route('/profile', name: 'api_user_update_profile', methods: ['PATCH'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                return $this->json(
                    ['error' => 'Vous devez être connecté'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(
                    ['error' => 'Format JSON invalide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $constraints = new Assert\Collection([
                'firstname' => new Assert\Optional([
                    new Assert\NotBlank(message: 'Le prénom ne peut pas être vide'),
                    new Assert\Length(max: 100)
                ]),
                'lastname' => new Assert\Optional([
                    new Assert\NotBlank(message: 'Le nom ne peut pas être vide'),
                    new Assert\Length(max: 100)
                ]),
                'email' => new Assert\Optional([
                    new Assert\NotBlank(message: 'L\'email ne peut pas être vide'),
                    new Assert\Email(message: 'Format d\'email invalide'),
                ]),
                'city' => new Assert\Optional([
                    new Assert\Length(max: 255)
                ]),
                'zipcode' => new Assert\Optional([
                    new Assert\Length(max: 20)
                ]),
                'password' => new Assert\Optional([
                    new Assert\Length(
                        min: 6,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                    ),
                ]),
            ]);

            $violations = $validator->validate($data, $constraints);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
                $existingUser = $em->getRepository(User::class)
                    ->findOneBy(['email' => $data['email']]);

                if ($existingUser) {
                    return $this->json(
                        ['error' => 'Cet email est déjà utilisé'],
                        Response::HTTP_CONFLICT
                    );
                }
                $user->setEmail($data['email']);
            }

            if (isset($data['firstname'])) {
                $user->setFirstname($data['firstname']);
            }

            if (isset($data['lastname'])) {
                $user->setLastname($data['lastname']);
            }

            if (isset($data['city'])) {
                $user->setCity($data['city']);
            }

            if (isset($data['zipcode'])) {
                $user->setZipcode($data['zipcode']);
            }

            if (isset($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            return $this->json([
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'city' => $user->getCity(),
                    'zipcode' => $user->getZipcode(),
                    'status' => $user->getStatus()->value,
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour du profil'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('/user', name: 'api_user_list', methods: ['GET'])]
    public function list(UserRepository $repository): JsonResponse
    {
        $users = $repository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail(),
                'city' => $user->getCity(),
                'zipcode' => $user->getZipcode(),
                'status' => $user->getStatus(),
                'jobOffersCount' => count($user->getJobOffers()),
            ];
        }

        return $this->json($data, 200);
    }

    #[Route('/user', name: 'api_user_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        if (!isset($data['lastname']) || empty($data['lastname'])) {
            return $this->json(['error' => 'Le nom est obligatoire'], 400);
        }
        if (!isset($data['firstname']) || empty($data['firstname'])) {
            return $this->json(['error' => 'Le prénom est obligatoire'], 400);
        }



        $user = new User();
        $user->setLastName($data['lastname'] ?? null);
        $user->setFirstName($data['firstname'] ?? null);
        $user->setEmail($data['email'] ?? null);
        $user->setCity($data['city'] ?? null);
        $user->setZipcode($data['zipcode'] ?? null);
        $user->setStatus($data['status'] ?? null);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'id' => $user->getId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'email' => $user->getEmail(),
            'city' => $user->getCity(),
            'zipcode' => $user->getZipcode(),
            'status' => $user->getStatus(),
        ], 201);
    }
}
