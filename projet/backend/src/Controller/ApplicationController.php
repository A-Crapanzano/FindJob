<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\ApplicationStatusEnum;
use App\Enum\StatusEnum;
use App\Repository\ApplicationRepository;
use App\Repository\JobOfferRepository;
use App\Service\CloudflareR2Service;
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
class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/job-offers/{id}/apply', name: 'api_jobs_apply', methods: ['POST'])]
    public function apply(
        int $id,
        Request $request,
        JobOfferRepository $jobOfferRepo,
        ApplicationRepository $applicationRepo,
        CloudflareR2Service $r2Service,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                return $this->json(
                    ['error' => 'Vous devez être connecté'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if ($user->getStatus() !== StatusEnum::CANDIDATE) {
                return $this->json(
                    ['error' => 'Seuls les candidats peuvent postuler à une offre'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $jobOffer = $jobOfferRepo->find($id);
            if (!$jobOffer) {
                return $this->json(
                    ['error' => 'Offre d\'emploi introuvable'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $existingApplication = $applicationRepo->findOneBy([
                'jobOffer' => $jobOffer,
                'user' => $user
            ]);

            if ($existingApplication) {
                return $this->json(
                    ['error' => 'Vous avez déjà postulé à cette offre'],
                    Response::HTTP_CONFLICT
                );
            }

            $resumeFile = $request->files->get('resume');
            $message = $request->request->get('message');

            if ($message) {
                $constraints = new Assert\Collection([
                    'message' => new Assert\Optional([
                        new Assert\Length(
                            max: 2000,
                            maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères'
                        )
                    ]),
                ]);

                $violations = $this->validator->validate(['message' => $message], $constraints);

                if (count($violations) > 0) {
                    $errors = [];
                    foreach ($violations as $violation) {
                        $errors[$violation->getPropertyPath()] = $violation->getMessage();
                    }
                    return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
                }
            }

            $application = new Application();
            $application->setJobOffer($jobOffer);
            $application->setUser($user);

            if ($message) {
                $application->setMessage($message);
            }

            if ($resumeFile) {
                $uploadResult = $r2Service->uploadFile($resumeFile, 'resumes');

                if (!$uploadResult['success']) {
                    return $this->json(
                        ['error' => $uploadResult['error']],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $application->setResumeUrl($uploadResult['url']);
                $application->setResumeKey($uploadResult['key']);
            }

            $jobOffer->incrementApplicationsCount();

            $this->entityManager->persist($application);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Candidature envoyée avec succès',
                'application' => [
                    'id' => $application->getId(),
                    'jobOfferId' => $jobOffer->getId(),
                    'jobTitle' => $jobOffer->getTitle(),
                    'companyName' => $jobOffer->getCompany()->getName(),
                    'message' => $application->getMessage(),
                    'resumeUrl' => $application->getResumeUrl(),
                    'status' => $application->getStatus()->value,
                    'appliedAt' => $application->getAppliedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de l\'envoi de la candidature'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/applications', name: 'api_applications_list', methods: ['GET'])]
    public function listMyApplications(
        ApplicationRepository $repository,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ['error' => 'Vous devez être connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($user->getStatus() !== StatusEnum::CANDIDATE) {
            return $this->json(
                ['error' => 'Seuls les candidats ont des candidatures'],
                Response::HTTP_FORBIDDEN
            );
        }

        $applications = $repository->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('a.jobOffer', 'j')
            ->addSelect('j')
            ->leftJoin('j.company', 'c')
            ->addSelect('c')
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($applications as $application) {
            $data[] = [
                'id' => $application->getId(),
                'status' => $application->getStatus()->value,
                'message' => $application->getMessage(),
                'resumeUrl' => $application->getResumeUrl(),
                'appliedAt' => $application->getAppliedAt()->format('Y-m-d H:i:s'),
                'jobOffer' => [
                    'id' => $application->getJobOffer()->getId(),
                    'title' => $application->getJobOffer()->getTitle(),
                    'location' => $application->getJobOffer()->getLocation(),
                    'contractType' => $application->getJobOffer()->getContractType()?->value,
                    'remoteType' => $application->getJobOffer()->getRemoteType()->value,
                ],
                'company' => [
                    'id' => $application->getJobOffer()->getCompany()->getId(),
                    'name' => $application->getJobOffer()->getCompany()->getName(),
                ],
            ];
        }

        return $this->json([
            'total' => count($data),
            'applications' => $data
        ], Response::HTTP_OK);
    }

    #[Route('/applications/{id}', name: 'api_applications_get', methods: ['GET'])]
    public function getOne(
        int $id,
        ApplicationRepository $repository,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ['error' => 'Vous devez être connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $application = $repository->find($id);

        if (!$application) {
            return $this->json(
                ['error' => 'Candidature introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        $isCandidate = $application->getUser()->getId() === $user->getId();
        $isRecruiter = $user->getStatus() === StatusEnum::RECRUITER
            && $user->getCompany()
            && $application->getJobOffer()->getCompany()->getId() === $user->getCompany()->getId();

        if (!$isCandidate && !$isRecruiter) {
            return $this->json(
                ['error' => 'Vous n\'avez pas accès à cette candidature'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = [
            'id' => $application->getId(),
            'status' => $application->getStatus()->value,
            'message' => $application->getMessage(),
            'resumeUrl' => $application->getResumeUrl(),
            'appliedAt' => $application->getAppliedAt()->format('Y-m-d H:i:s'),
            'jobOffer' => [
                'id' => $application->getJobOffer()->getId(),
                'title' => $application->getJobOffer()->getTitle(),
                'location' => $application->getJobOffer()->getLocation(),
                'contractType' => $application->getJobOffer()->getContractType()?->value,
                'remoteType' => $application->getJobOffer()->getRemoteType()->value,
                'description' => $application->getJobOffer()->getDescription(),
                'requirements' => $application->getJobOffer()->getRequirements(),
                'salaryMin' => $application->getJobOffer()->getSalaryMin(),
                'salaryMax' => $application->getJobOffer()->getSalaryMax(),
            ],
            'company' => [
                'id' => $application->getJobOffer()->getCompany()->getId(),
                'name' => $application->getJobOffer()->getCompany()->getName(),
                'location' => $application->getJobOffer()->getCompany()->getLocation(),
                'website' => $application->getJobOffer()->getCompany()->getWebsite(),
            ],
        ];

        if ($isRecruiter) {
            $data['candidate'] = [
                'id' => $application->getUser()->getId(),
                'email' => $application->getUser()->getEmail(),
                'firstName' => $application->getUser()->getFirstName(),
                'lastName' => $application->getUser()->getLastName(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/applications/{id}/status', name: 'api_applications_update_status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        Request $request,
        ApplicationRepository $repository,
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
                    ['error' => 'Seuls les recruteurs peuvent modifier le statut d\'une candidature'],
                    Response::HTTP_FORBIDDEN
                );
            }

            if (!$user->getCompany()) {
                return $this->json(
                    ['error' => 'Vous devez avoir une entreprise'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $application = $repository->find($id);

            if (!$application) {
                return $this->json(
                    ['error' => 'Candidature introuvable'],
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($application->getJobOffer()->getCompany()->getId() !== $user->getCompany()->getId()) {
                return $this->json(
                    ['error' => 'Vous ne pouvez pas modifier cette candidature'],
                    Response::HTTP_FORBIDDEN
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
                'status' => [
                    new Assert\NotBlank(message: 'Le statut est obligatoire'),
                    new Assert\Choice(
                        choices: ['pending', 'accepted', 'rejected'],
                        message: 'Statut invalide. Valeurs possibles: pending, accepted, rejected'
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

            $statusEnum = ApplicationStatusEnum::tryFrom($data['status']);
            if (!$statusEnum) {
                return $this->json(
                    ['error' => 'Statut invalide'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $application->setStatus($statusEnum);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Statut de la candidature mis à jour avec succès',
                'application' => [
                    'id' => $application->getId(),
                    'status' => $application->getStatus()->value,
                    'appliedAt' => $application->getAppliedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la mise à jour du statut'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/applications/{id}', name: 'api_applications_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        ApplicationRepository $repository,
        CloudflareR2Service $r2Service,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                return $this->json(
                    ['error' => 'Vous devez être connecté'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if ($user->getStatus() !== StatusEnum::CANDIDATE) {
                return $this->json(
                    ['error' => 'Seuls les candidats peuvent retirer leur candidature'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $application = $repository->find($id);

            if (!$application) {
                return $this->json(
                    ['error' => 'Candidature introuvable'],
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($application->getUser()->getId() !== $user->getId()) {
                return $this->json(
                    ['error' => 'Vous ne pouvez pas supprimer cette candidature'],
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($application->getResumeKey()) {
                $r2Service->deleteFile($application->getResumeKey());
            }

            $jobOffer = $application->getJobOffer();
            $jobOffer->decrementApplicationsCount();

            $this->entityManager->remove($application);
            $this->entityManager->flush();

            return $this->json(
                ['message' => 'Candidature retirée avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Une erreur est survenue lors de la suppression de la candidature'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/job-offers/{id}/applications', name: 'api_job_applications_list', methods: ['GET'])]
    public function listJobApplications(
        int $id,
        JobOfferRepository $jobOfferRepo,
        ApplicationRepository $applicationRepo,
        CloudflareR2Service $r2Service,
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
                ['error' => 'Seuls les recruteurs peuvent voir les candidatures'],
                Response::HTTP_FORBIDDEN
            );
        }

        $jobOffer = $jobOfferRepo->find($id);

        if (!$jobOffer) {
            return $this->json(
                ['error' => 'Offre d\'emploi introuvable'],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($jobOffer->getUser()->getId() !== $user->getId()) {
            return $this->json(
                ['error' => 'Vous n\'avez pas accès aux candidatures de cette offre'],
                Response::HTTP_FORBIDDEN
            );
        }

        $applications = $applicationRepo->createQueryBuilder('a')
            ->where('a.jobOffer = :jobOffer')
            ->setParameter('jobOffer', $jobOffer)
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($applications as $application) {
            $resumeUrl = null;
            if ($application->getResumeKey()) {
                $resumeUrl = $r2Service->getSignedUrl($application->getResumeKey(), 3600); // Valide 1h
            }

            $data[] = [
                'id' => $application->getId(),
                'status' => $application->getStatus()->value,
                'message' => $application->getMessage(),
                'resumeUrl' => $resumeUrl, // ← URL signée au lieu de l'URL stockée
                'appliedAt' => $application->getAppliedAt()->format('Y-m-d H:i:s'),
                'candidate' => [
                    'id' => $application->getUser()->getId(),
                    'email' => $application->getUser()->getEmail(),
                    'firstname' => $application->getUser()->getFirstname(),
                    'lastname' => $application->getUser()->getLastname(),
                    'city' => $application->getUser()->getCity(),
                    'zipcode' => $application->getUser()->getZipcode(),
                ],
            ];
        }

        return $this->json([
            'jobOffer' => [
                'id' => $jobOffer->getId(),
                'title' => $jobOffer->getTitle(),
            ],
            'total' => count($data),
            'applications' => $data
        ], Response::HTTP_OK);
    }
}
