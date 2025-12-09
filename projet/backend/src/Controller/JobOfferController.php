<?php

namespace App\Controller;

use App\Entity\JobOffer;
use App\Entity\User;
use App\Enum\StatusEnum;
use App\Enum\ContractTypeEnum;
use App\Enum\RemoteTypeEnum;
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
class JobOfferController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/job-offers', name: 'api_jobs_list', methods: ['GET'])]
    public function listJobs(Request $request, JobOfferRepository $repository): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 20)));

        $location = $request->query->get('location');
        $contractType = $request->query->get('contract');
        $remoteType = $request->query->get('remote');
        $salaryMin = $request->query->get('salaryMin');
        $salaryMax = $request->query->get('salaryMax');
        $search = $request->query->get('search');

        $qb = $repository->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->addSelect('c');

        if ($location) {
            $qb->andWhere('j.location LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($contractType) {
            $contractEnum = ContractTypeEnum::tryFrom($contractType);
            if ($contractEnum) {
                $qb->andWhere('j.contractType = :contractType')
                    ->setParameter('contractType', $contractEnum);
            }
        }

        if ($remoteType) {
            $remoteEnum = RemoteTypeEnum::tryFrom($remoteType);
            if ($remoteEnum) {
                $qb->andWhere('j.remoteType = :remoteType')
                    ->setParameter('remoteType', $remoteEnum);
            }
        }

        if ($salaryMin !== null && is_numeric($salaryMin)) {
            $qb->andWhere('j.salaryMax >= :salaryMin OR j.salaryMax IS NULL')
                ->setParameter('salaryMin', (int)$salaryMin);
        }

        if ($salaryMax !== null && is_numeric($salaryMax)) {
            $qb->andWhere('j.salaryMin <= :salaryMax OR j.salaryMin IS NULL')
                ->setParameter('salaryMax', (int)$salaryMax);
        }

        if ($search) {
            $qb->andWhere('j.title LIKE :search OR j.description LIKE :search OR j.requirements LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('j.createdAt', 'DESC');

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(j.id)')->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

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
                'companyId' => $offer->getCompany()->getId(),
                'companyName' => $offer->getCompany()->getName(),
            ];
        }

        $totalPages = ceil($total / $limit);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
                'hasNextPage' => $page < $totalPages,
                'hasPrevPage' => $page > 1,
            ],
            'filters' => [
                'location' => $location,
                'contract' => $contractType,
                'remote' => $remoteType,
                'salaryMin' => $salaryMin,
                'salaryMax' => $salaryMax,
                'search' => $search,
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/job-offers/{id}', name: 'api_jobs_get', methods: ['GET'])]
    public function getOne(int $id, JobOfferRepository $repository): JsonResponse
    {
        $offer = $repository->find($id);

        if (!$offer) {
            return $this->json(
                ['error' => 'Offre d\'emploi introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json([
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
            'company' => [
                'id' => $offer->getCompany()->getId(),
                'name' => $offer->getCompany()->getName(),
                'location' => $offer->getCompany()->getLocation(),
                'website' => $offer->getCompany()->getWebsite(),
                'size' => $offer->getCompany()->getSize()?->value,
            ],
            'recruiter' => [
                'id' => $offer->getUser()->getId(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/job-offers', name: 'api_jobs_create', methods: ['POST'])]
    public function create(
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
                    ['error' => 'Seuls les recruteurs peuvent créer une offre d\'emploi'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $company = $user->getCompany();
            if (!$company) {
                return $this->json(
                    ['error' => 'Vous devez avoir une entreprise pour créer une offre'],
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
                'title' => [
                    new Assert\NotBlank(message: 'Le titre de l\'offre est obligatoire'),
                    new Assert\Length(
                        min: 3,
                        max: 255,
                        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
                'description' => [
                    new Assert\NotBlank(message: 'La description est obligatoire'),
                    new Assert\Length(
                        min: 10,
                        max: 5000,
                        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
                'location' => new Assert\Optional([
                    new Assert\Length(max: 255)
                ]),
                'contractType' => new Assert\Optional([
                    new Assert\Choice(
                        choices: ['CDI', 'CDD', 'Stage', 'Alternance'],
                        message: 'Type de contrat invalide. Valeurs possibles: CDI, CDD, Stage, Alternance'
                    )
                ]),
                'remoteType' => new Assert\Optional([
                    new Assert\Choice(
                        choices: ['onsite', 'remote', 'hybrid'],
                        message: 'Type de télétravail invalide. Valeurs possibles: onsite, remote, hybrid'
                    )
                ]),
                'salaryMin' => new Assert\Optional([
                    new Assert\Type('integer', message: 'Le salaire minimum doit être un nombre entier'),
                    new Assert\PositiveOrZero(message: 'Le salaire minimum doit être positif ou zéro')
                ]),
                'salaryMax' => new Assert\Optional([
                    new Assert\Type('integer', message: 'Le salaire maximum doit être un nombre entier'),
                    new Assert\PositiveOrZero(message: 'Le salaire maximum doit être positif ou zéro')
                ]),
                'requirements' => new Assert\Optional([
                    new Assert\Type('array', message: 'Les exigences doivent être un tableau'),
                    new Assert\All([
                        new Assert\NotBlank(message: 'Une exigence ne peut pas être vide'),
                        new Assert\Length(
                            max: 500,
                            maxMessage: 'Une exigence ne peut pas dépasser {{ limit }} caractères'
                        )
                    ])
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

            if (isset($data['salaryMin']) && isset($data['salaryMax'])) {
                if ($data['salaryMin'] > $data['salaryMax']) {
                    return $this->json(
                        ['error' => 'Le salaire minimum ne peut pas être supérieur au salaire maximum'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            $jobOffer = new JobOffer();
            $jobOffer->setTitle($data['title']);
            $jobOffer->setDescription($data['description']);
            $jobOffer->setCompany($company);
            $jobOffer->setUser($user);

            if (isset($data['location'])) {
                $jobOffer->setLocation($data['location']);
            }

            if (isset($data['contractType'])) {
                $contractEnum = ContractTypeEnum::tryFrom($data['contractType']);
                if ($contractEnum) {
                    $jobOffer->setContractType($contractEnum);
                }
            }

            if (isset($data['remoteType'])) {
                $remoteEnum = RemoteTypeEnum::tryFrom($data['remoteType']);
                if ($remoteEnum) {
                    $jobOffer->setRemoteType($remoteEnum);
                }
            } else {
                $jobOffer->setRemoteType(RemoteTypeEnum::ONSITE);
            }

            if (isset($data['salaryMin'])) {
                $jobOffer->setSalaryMin($data['salaryMin']);
            }

            if (isset($data['salaryMax'])) {
                $jobOffer->setSalaryMax($data['salaryMax']);
            }

            if (isset($data['requirements']) && is_array($data['requirements'])) {
                $cleanRequirements = array_filter(
                    array_map('trim', $data['requirements']),
                    fn($req) => !empty($req)
                );
                $cleanRequirements = array_values($cleanRequirements);
                $jobOffer->setRequirements($cleanRequirements);
            }

            $this->entityManager->persist($jobOffer);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Offre d\'emploi créée avec succès',
                'job' => [
                    'id' => $jobOffer->getId(),
                    'title' => $jobOffer->getTitle(),
                    'location' => $jobOffer->getLocation(),
                    'contractType' => $jobOffer->getContractType()?->value,
                    'remoteType' => $jobOffer->getRemoteType()->value,
                    'salaryMin' => $jobOffer->getSalaryMin(),
                    'salaryMax' => $jobOffer->getSalaryMax(),
                    'description' => $jobOffer->getDescription(),
                    'requirements' => $jobOffer->getRequirements(),
                    'applicationsCount' => $jobOffer->getApplicationsCount(),
                    'createdAt' => $jobOffer->getCreatedAt()->format('Y-m-d H:i:s'),
                    'companyId' => $company->getId(),
                    'companyName' => $company->getName(),
                    'userId' => $user->getId(),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la création de l\'offre'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/my-job-offers', name: 'api_my_jobs_list', methods: ['GET'])]
    public function listMyJobs(
        JobOfferRepository $repository,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ['error' => 'Vous devez être connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($user->getStatus() !== StatusEnum::RECRUITER) {
            return $this->json(
                ['error' => 'Seuls les recruteurs ont des offres d\'emploi'],
                Response::HTTP_FORBIDDEN
            );
        }

        $offers = $repository->createQueryBuilder('j')
            ->where('j.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('j.company', 'c')
            ->addSelect('c')
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

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
                'company' => [
                    'id' => $offer->getCompany()->getId(),
                    'name' => $offer->getCompany()->getName(),
                ],
            ];
        }

        return $this->json([
            'total' => count($data),
            'offers' => $data
        ], Response::HTTP_OK);
    }
}
