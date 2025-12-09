<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\CompanySizeEnum;
use App\Enum\StatusEnum;
use App\Repository\CompanyRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
class CompanyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/my-company', name: 'api_my_company_create', methods: ['POST'])]
    public function createMyCompany(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                return $this->json(
                    ['error' => 'Vous devez être connecté'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if ($user->getStatus() !== StatusEnum::RECRUITER) {
                return $this->json(
                    ['error' => 'Seuls les recruteurs peuvent créer une entreprise'],
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($user->getCompany()) {
                return $this->json(
                    ['error' => 'Vous avez déjà une entreprise'],
                    Response::HTTP_BAD_REQUEST
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
                'name' => [
                    new Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
                'description' => new Assert\Optional([
                    new Assert\Length(max: 5000)
                ]),
                'website' => new Assert\Optional([
                    new Assert\Url(message: 'L\'URL du site web n\'est pas valide'),
                    new Assert\Length(max: 255)
                ]),
                'location' => new Assert\Optional([
                    new Assert\Length(max: 255)
                ]),
                'size' => new Assert\Optional([
                    new Assert\Choice(
                        choices: ['startup', 'small', 'medium', 'large', 'enterprise'],
                        message: 'Taille invalide. Valeurs possibles: startup, small, medium, large, enterprise'
                    )
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

            $user->setCompany($company);

            $this->entityManager->persist($company);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Entreprise créée avec succès',
                'company' => [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                    'description' => $company->getDescription(),
                    'website' => $company->getWebsite(),
                    'location' => $company->getLocation(),
                    'size' => $company->getSize()?->value,
                    'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création de l\'entreprise'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/my-company', name: 'api_my_company_get', methods: ['GET'])]
    public function getMyCompany(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(
                ['error' => 'Vous devez être connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($user->getStatus() !== StatusEnum::RECRUITER) {
            return $this->json(
                ['error' => 'Seuls les recruteurs ont une entreprise'],
                Response::HTTP_FORBIDDEN
            );
        }

        $company = $user->getCompany();

        if (!$company) {
            return $this->json(
                ['error' => 'Vous n\'avez pas encore créé d\'entreprise'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json([
            'id' => $company->getId(),
            'name' => $company->getName(),
            'description' => $company->getDescription(),
            'website' => $company->getWebsite(),
            'location' => $company->getLocation(),
            'size' => $company->getSize()?->value,
            'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
            'jobOffersCount' => $company->getJobOffers()->count(),
            'recruitersCount' => $company->getRecruiters()->count(),
        ], Response::HTTP_OK);
    }

    #[Route('/my-company', name: 'api_my_company_update', methods: ['PUT'])]
    public function updateMyCompany(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                return $this->json(
                    ['error' => 'Vous devez être connecté'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if ($user->getStatus() !== StatusEnum::RECRUITER) {
                return $this->json(
                    ['error' => 'Seuls les recruteurs peuvent modifier une entreprise'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $company = $user->getCompany();

            if (!$company) {
                return $this->json(
                    ['error' => 'Vous n\'avez pas d\'entreprise à modifier'],
                    Response::HTTP_NOT_FOUND
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
                'name' => new Assert\Optional([
                    new Assert\NotBlank(message: 'Le nom ne peut pas être vide'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ]),
                'description' => new Assert\Optional([
                    new Assert\Length(max: 5000)
                ]),
                'website' => new Assert\Optional([
                    new Assert\Url(message: 'L\'URL du site web n\'est pas valide'),
                    new Assert\Length(max: 255)
                ]),
                'location' => new Assert\Optional([
                    new Assert\Length(max: 255)
                ]),
                'size' => new Assert\Optional([
                    new Assert\Choice(
                        choices: ['startup', 'small', 'medium', 'large', 'enterprise'],
                        message: 'Taille invalide. Valeurs possibles: startup, small, medium, large, enterprise'
                    )
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

            return $this->json([
                'message' => 'Entreprise mise à jour avec succès',
                'company' => [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                    'description' => $company->getDescription(),
                    'website' => $company->getWebsite(),
                    'location' => $company->getLocation(),
                    'size' => $company->getSize()?->value,
                    'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour de l\'entreprise'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/my-company', name: 'api_my_company_delete', methods: ['DELETE'])]
    public function deleteMyCompany(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json(
                    ['error' => 'Vous devez être connecté'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if ($user->getStatus() !== StatusEnum::RECRUITER) {
                return $this->json(
                    ['error' => 'Seuls les recruteurs peuvent supprimer une entreprise'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $company = $user->getCompany();

            if (!$company) {
                return $this->json(
                    ['error' => 'Vous n\'avez pas d\'entreprise à supprimer'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $user->setCompany(null);

            $this->entityManager->remove($company);
            $this->entityManager->flush();

            return $this->json(
                ['message' => 'Entreprise supprimée avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression de l\'entreprise'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/companies/{id}', name: 'api_companies_get', methods: ['GET'])]
    public function getOne(int $id, CompanyRepository $repository): JsonResponse
    {
        $company = $repository->find($id);

        if (!$company) {
            return $this->json(
                ['error' => 'Entreprise introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json([
            'id' => $company->getId(),
            'name' => $company->getName(),
            'description' => $company->getDescription(),
            'website' => $company->getWebsite(),
            'location' => $company->getLocation(),
            'size' => $company->getSize()?->value,
            'createdAt' => $company->getCreatedAt()->format('Y-m-d H:i:s'),
            'jobOffersCount' => $company->getJobOffers()->count(),
        ], Response::HTTP_OK);
    }

    #[Route('/companies/{id}/jobs', name: 'api_companies_jobs', methods: ['GET'])]
    public function getCompanyJobs(
        int $id,
        CompanyRepository $companyRepo,
        JobOfferRepository $jobRepo
    ): JsonResponse {
        $company = $companyRepo->find($id);

        if (!$company) {
            return $this->json(
                ['error' => 'Entreprise introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $qb = $jobRepo->createQueryBuilder('j')
            ->where('j.company = :companyId')
            ->setParameter('companyId', $id)
            ->leftJoin('j.user', 'u')
            ->addSelect('u')
            ->orderBy('j.createdAt', 'DESC');

        $offers = $qb->getQuery()->getResult();

        $data = [];
        foreach ($offers as $offer) {
            $data[] = [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'location' => $offer->getLocation(),
                'contractType' => $offer->getContractType()?->value,
                'remoteType' => $offer->getRemoteType()->value,
                'salaryMin' => $offer->getSalaryMin(),
                'salaryMax' => $offer->getSalaryMax(),
                'description' => $offer->getDescription(),
                'requirements' => $offer->getRequirements(),
                'applicationsCount' => $offer->getApplicationsCount(),
                'createdAt' => $offer->getCreatedAt()->format('Y-m-d H:i:s'),
                'userId' => $offer->getUser()->getId(),
            ];
        }

        return $this->json([
            'company' => [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'location' => $company->getLocation(),
            ],
            'total' => count($data),
            'jobs' => $data,
        ], Response::HTTP_OK);
    }
}
