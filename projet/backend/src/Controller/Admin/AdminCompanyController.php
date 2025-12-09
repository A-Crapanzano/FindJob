<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Enum\CompanySizeEnum;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
#[IsGranted('ROLE_ADMIN')]
class AdminCompanyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/admin/companies', name: 'api_admin_company_list', methods: ['GET'])]
    public function list(CompanyRepository $repository): JsonResponse
    {
        $companies = $repository->findAll();

        $data = [];
        foreach ($companies as $company) {
            $data[] = [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'location' => $company->getLocation(),
                'size' => $company->getSize()?->value,
                'website' => $company->getWebsite(),
                'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
                'jobOffersCount' => $company->getJobOffers()->count(),
                'recruitersCount' => $company->getRecruiters()->count(),
            ];
        }

        return $this->json(['total' => count($data), 'companies' => $data]);
    }

    #[Route('/admin/companies/{id}', name: 'api_admin_company_detail', methods: ['GET'])]
    public function getOne(int $id, CompanyRepository $repository): JsonResponse
    {
        $company = $repository->find($id);

        if (!$company) {
            return $this->json(['error' => 'Entreprise introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $company->getId(),
            'name' => $company->getName(),
            'description' => $company->getDescription(),
            'location' => $company->getLocation(),
            'size' => $company->getSize()?->value,
            'website' => $company->getWebsite(),
            'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
            'jobOffersCount' => $company->getJobOffers()->count(),
            'recruitersCount' => $company->getRecruiters()->count(),
        ]);
    }

    #[Route('/admin/companies', name: 'api_admin_company_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 2, max: 255),
            ],
            'description' => new Assert\Optional(),
            'website' => new Assert\Optional([new Assert\Url()]),
            'location' => new Assert\Optional(),
            'size' => new Assert\Optional([
                new Assert\Choice(choices: ['startup', 'small', 'medium', 'large', 'enterprise']),
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

        $company = new Company();
        $company->setName($data['name']);
        $company->setDescription($data['description'] ?? null);
        $company->setWebsite($data['website'] ?? null);
        $company->setLocation($data['location'] ?? null);

        if (isset($data['size'])) {
            $sizeEnum = CompanySizeEnum::tryFrom($data['size']);
            if ($sizeEnum) {
                $company->setSize($sizeEnum);
            }
        }

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Entreprise créée',
            'company' => [
                'id' => $company->getId(),
                'name' => $company->getName(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/admin/companies/{id}', name: 'api_admin_company_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, CompanyRepository $repository): JsonResponse
    {
        $company = $repository->find($id);

        if (!$company) {
            return $this->json(['error' => 'Entreprise introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $company->setName($data['name']);
        }

        if (isset($data['description'])) {
            $company->setDescription($data['description']);
        }

        if (isset($data['website'])) {
            $company->setWebsite($data['website']);
        }

        if (isset($data['location'])) {
            $company->setLocation($data['location']);
        }

        if (isset($data['size'])) {
            $sizeEnum = CompanySizeEnum::tryFrom($data['size']);
            if ($sizeEnum) {
                $company->setSize($sizeEnum);
            }
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Entreprise mise à jour']);
    }

    #[Route('/admin/companies/{id}', methods: ['DELETE'])]
    public function delete(int $id, CompanyRepository $repository): JsonResponse
    {
        $company = $repository->find($id);

        if (!$company) {
            return $this->json(['error' => 'Entreprise introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        return $this->json(['message' => 'Entreprise supprimée']);
    }
}
