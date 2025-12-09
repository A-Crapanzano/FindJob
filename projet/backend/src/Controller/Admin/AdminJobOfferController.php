<?php

namespace App\Controller\Admin;

use App\Entity\JobOffer;
use App\Enum\ContractTypeEnum;
use App\Enum\RemoteTypeEnum;
use App\Repository\JobOfferRepository;
use App\Repository\UserRepository;
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
class AdminJobOfferController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/admin/job-offers', name: 'api_admin_job_offer_list', methods: ['GET'])]
    public function list(JobOfferRepository $repository): JsonResponse
    {
        $offers = $repository->createQueryBuilder('j')
            ->leftJoin('j.company', 'c')
            ->addSelect('c')
            ->leftJoin('j.user', 'u')
            ->addSelect('u')
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
                'applicationsCount' => $offer->getApplicationsCount(),
                'createdAt' => $offer->getCreatedAt()->format('Y-m-d H:i:s'),
                'company' => [
                    'id' => $offer->getCompany()->getId(),
                    'name' => $offer->getCompany()->getName(),
                ],
                'recruiter' => [
                    'id' => $offer->getUser()->getId(),
                    'email' => $offer->getUser()->getEmail(),
                ],
            ];
        }

        return $this->json(['total' => count($data), 'offers' => $data]);
    }

    #[Route('/admin/job-offers/{id}', name: 'api_admin_job_offer_detail', methods: ['GET'])]
    public function getOne(int $id, JobOfferRepository $repository): JsonResponse
    {
        $offer = $repository->find($id);

        if (!$offer) {
            return $this->json(['error' => 'Offre introuvable'], Response::HTTP_NOT_FOUND);
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
            ],
            'recruiter' => [
                'id' => $offer->getUser()->getId(),
                'email' => $offer->getUser()->getEmail(),
                'firstname' => $offer->getUser()->getFirstname(),
                'lastname' => $offer->getUser()->getLastname(),
            ],
        ]);
    }

    #[Route('/admin/job-offers', name: 'api_admin_job_offer_create', methods: ['POST'])]
    public function create(Request $request, UserRepository $userRepo, CompanyRepository $companyRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $constraints = new Assert\Collection([
            'title' => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 255)],
            'description' => [new Assert\NotBlank()],
            'userId' => [new Assert\NotBlank(), new Assert\Type('integer')],
            'companyId' => [new Assert\NotBlank(), new Assert\Type('integer')],
            'location' => new Assert\Optional(),
            'contractType' => new Assert\Optional([
                new Assert\Choice(choices: ['CDI', 'CDD', 'Stage', 'Alternance']),
            ]),
            'remoteType' => new Assert\Optional([
                new Assert\Choice(choices: ['onsite', 'remote', 'hybrid']),
            ]),
            'salaryMin' => new Assert\Optional([new Assert\Type('integer')]),
            'salaryMax' => new Assert\Optional([new Assert\Type('integer')]),
            'requirements' => new Assert\Optional([new Assert\Type('array')]),
        ]);

        $violations = $this->validator->validate($data, $constraints);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepo->find($data['userId']);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $company = $companyRepo->find($data['companyId']);
        if (!$company) {
            return $this->json(['error' => 'Entreprise introuvable'], Response::HTTP_NOT_FOUND);
        }

        $jobOffer = new JobOffer();
        $jobOffer->setTitle($data['title']);
        $jobOffer->setDescription($data['description']);
        $jobOffer->setUser($user);
        $jobOffer->setCompany($company);
        $jobOffer->setLocation($data['location'] ?? null);

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
        }

        if (isset($data['salaryMin'])) {
            $jobOffer->setSalaryMin($data['salaryMin']);
        }

        if (isset($data['salaryMax'])) {
            $jobOffer->setSalaryMax($data['salaryMax']);
        }

        if (isset($data['requirements']) && is_array($data['requirements'])) {
            $jobOffer->setRequirements($data['requirements']);
        }

        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Offre créée',
            'offer' => [
                'id' => $jobOffer->getId(),
                'title' => $jobOffer->getTitle(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/admin/job-offers/{id}', name: 'api_admin_job_offer_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, JobOfferRepository $repository, UserRepository $userRepo, CompanyRepository $companyRepo): JsonResponse
    {
        $offer = $repository->find($id);

        if (!$offer) {
            return $this->json(['error' => 'Offre introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) {
            $offer->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $offer->setDescription($data['description']);
        }

        if (isset($data['location'])) {
            $offer->setLocation($data['location']);
        }

        if (isset($data['userId'])) {
            $user = $userRepo->find($data['userId']);
            if ($user) {
                $offer->setUser($user);
            }
        }

        if (isset($data['companyId'])) {
            $company = $companyRepo->find($data['companyId']);
            if ($company) {
                $offer->setCompany($company);
            }
        }

        if (isset($data['contractType'])) {
            $contractEnum = ContractTypeEnum::tryFrom($data['contractType']);
            if ($contractEnum) {
                $offer->setContractType($contractEnum);
            }
        }

        if (isset($data['remoteType'])) {
            $remoteEnum = RemoteTypeEnum::tryFrom($data['remoteType']);
            if ($remoteEnum) {
                $offer->setRemoteType($remoteEnum);
            }
        }

        if (isset($data['salaryMin'])) {
            $offer->setSalaryMin($data['salaryMin']);
        }

        if (isset($data['salaryMax'])) {
            $offer->setSalaryMax($data['salaryMax']);
        }

        if (isset($data['requirements']) && is_array($data['requirements'])) {
            $offer->setRequirements($data['requirements']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Offre mise à jour']);
    }

    #[Route('/admin/job-offers/{id}', name: 'api_admin_job_offer_delete', methods: ['DELETE'])]
    public function delete(int $id, JobOfferRepository $repository): JsonResponse
    {
        $offer = $repository->find($id);

        if (!$offer) {
            return $this->json(['error' => 'Offre introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($offer);
        $this->entityManager->flush();

        return $this->json(['message' => 'Offre supprimée']);
    }
}
