<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\StatusEnum;
use App\Repository\UserRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[Route('/admin/users', name: 'api_admin_user_list', methods: ['GET'])]
    public function list(UserRepository $repository): JsonResponse
    {
        $users = $repository->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'city' => $user->getCity(),
                'zipcode' => $user->getZipcode(),
                'status' => $user->getStatus()->value,
                'roles' => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'companyId' => $user->getCompany()?->getId(),
                'companyName' => $user->getCompany()?->getName(),
            ];
        }

        return $this->json(['total' => count($data), 'users' => $data]);
    }

    #[Route('/admin/users/{id}', name: 'api_admin_user_detail', methods: ['GET'])]
    public function getOne(int $id, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'city' => $user->getCity(),
            'zipcode' => $user->getZipcode(),
            'status' => $user->getStatus()->value,
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'company' => $user->getCompany() ? [
                'id' => $user->getCompany()->getId(),
                'name' => $user->getCompany()->getName(),
            ] : null,
        ]);
    }

    #[Route('/admin/users', name: 'api_admin_user_create', methods: ['POST'])]
    public function create(Request $request, CompanyRepository $companyRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
            'password' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 6),
            ],
            'firstname' => [new Assert\NotBlank()],
            'lastname' => [new Assert\NotBlank()],
            'city' => new Assert\Optional(),
            'zipcode' => new Assert\Optional(),
            'status' => [
                new Assert\NotBlank(),
                new Assert\Choice(choices: ['candidate', 'recruiter']),
            ],
            'companyId' => new Assert\Optional([
                new Assert\Type('integer'),
            ]),
            'roles' => new Assert\Optional([
                new Assert\Type('array'),
            ]),
        ]);

        $violations = $this->validator->validate($data, $constraints);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setCity($data['city'] ?? null);
        $user->setZipcode($data['zipcode'] ?? null);
        $user->setStatus(StatusEnum::tryFrom($data['status']));

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['companyId'])) {
            $company = $companyRepo->find($data['companyId']);
            if ($company) {
                $user->setCompany($company);
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur créé',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/admin/users/{id}', name: 'api_admin_user_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, UserRepository $repository, CompanyRepository $companyRepo): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
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

        if (isset($data['status'])) {
            $statusEnum = StatusEnum::tryFrom($data['status']);
            if ($statusEnum) {
                $user->setStatus($statusEnum);
            }
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['companyId'])) {
            $company = $companyRepo->find($data['companyId']);
            $user->setCompany($company);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/admin/users/{id}', name: 'api_admin_user_delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
