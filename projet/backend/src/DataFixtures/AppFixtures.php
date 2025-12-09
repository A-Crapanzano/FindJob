<?php

namespace App\DataFixtures;

use App\Entity\Application;
use App\Entity\Company;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Enum\ApplicationStatusEnum;
use App\Enum\CompanySizeEnum;
use App\Enum\ContractTypeEnum;
use App\Enum\JobStatusEnum;
use App\Enum\RemoteTypeEnum;
use App\Enum\StatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ========================================
        // 1. CRÉER DES UTILISATEURS
        // ========================================

        $users = [];

        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("candidate{$i}@example.com");
            $user->setPassword('password123');
            $user->setFirstname("Candidat");
            $user->setLastname("Numéro {$i}");
            $user->setCity(['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux'][array_rand(['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux'])]);
            $user->setZipcode(['75001', '69001', '13001', '31000', '33000'][array_rand(['75001', '69001', '13001', '31000', '33000'])]);
            $user->setStatus(StatusEnum::CANDIDATE);
            $user->setRoles(['ROLE_USER']);

            $manager->persist($user);
            $users['candidates'][] = $user;
        }

        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setEmail("recruiter{$i}@example.com");
            $user->setPassword('password123');
            $user->setFirstname("Recruteur");
            $user->setLastname("Numéro {$i}");
            $user->setCity(['Paris', 'Lyon', 'Nantes'][array_rand(['Paris', 'Lyon', 'Nantes'])]);
            $user->setZipcode(['75002', '69002', '44000'][array_rand(['75002', '69002', '44000'])]);
            $user->setStatus(StatusEnum::RECRUITER);
            $user->setRoles(['ROLE_USER']);

            $manager->persist($user);
            $users['recruiters'][] = $user;
        }

        $admin = new User();
        $admin->setEmail("admin@example.com");
        $admin->setPassword('admin123');
        $admin->setFirstname("Admin");
        $admin->setLastname("System");
        $admin->setCity("Paris");
        $admin->setZipcode("75000");
        $admin->setStatus(StatusEnum::RECRUITER);
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $manager->persist($admin);

        // ========================================
        // 2. CRÉER DES ENTREPRISES
        // ========================================

        $companies = [];

        $companiesData = [
            [
                'name' => 'Google France',
                'description' => 'Leader mondial de la recherche en ligne et des technologies cloud',
                'website' => 'https://google.fr',
                'location' => 'Paris',
                'size' => CompanySizeEnum::ENTERPRISE
            ],
            [
                'name' => 'Startup Innovation',
                'description' => 'Startup spécialisée dans l\'intelligence artificielle',
                'website' => 'https://startup-innovation.fr',
                'location' => 'Lyon',
                'size' => CompanySizeEnum::STARTUP
            ],
            [
                'name' => 'TechCorp',
                'description' => 'Société de conseil en technologies',
                'website' => 'https://techcorp.fr',
                'location' => 'Marseille',
                'size' => CompanySizeEnum::MEDIUM
            ],
            [
                'name' => 'Digital Agency',
                'description' => 'Agence digitale créative',
                'website' => 'https://digital-agency.fr',
                'location' => 'Bordeaux',
                'size' => CompanySizeEnum::SMALL
            ],
            [
                'name' => 'Enterprise Solutions',
                'description' => 'Solutions d\'entreprise pour grandes organisations',
                'website' => 'https://enterprise-solutions.fr',
                'location' => 'Paris',
                'size' => CompanySizeEnum::LARGE
            ],
        ];

        foreach ($companiesData as $data) {
            $company = new Company();
            $company->setName($data['name']);
            $company->setDescription($data['description']);
            $company->setWebsite($data['website']);
            $company->setLocation($data['location']);
            $company->setSize($data['size']);

            $manager->persist($company);
            $companies[] = $company;
        }

        // ========================================
        // 3. CRÉER DES OFFRES D'EMPLOI
        // ========================================

        $jobOffers = [];

        $jobsData = [
            [
                'title' => 'Développeur PHP Symfony',
                'description' => 'Nous recherchons un développeur PHP expérimenté avec Symfony pour rejoindre notre équipe.',
                'requirements' => 'Minimum 3 ans d\'expérience avec Symfony, connaissance de Doctrine, API REST',
                'location' => 'Paris',
                'contractType' => ContractTypeEnum::CDI,
                'remoteType' => RemoteTypeEnum::HYBRID,
                'salaryMin' => 40000,
                'salaryMax' => 55000,
            ],
            [
                'title' => 'Développeur Full Stack JavaScript',
                'description' => 'Rejoignez notre startup innovante en tant que développeur full stack.',
                'requirements' => 'React, Node.js, MongoDB, 2 ans d\'expérience minimum',
                'location' => 'Lyon',
                'contractType' => ContractTypeEnum::CDI,
                'remoteType' => RemoteTypeEnum::REMOTE,
                'salaryMin' => 35000,
                'salaryMax' => 45000,
            ],
            [
                'title' => 'Stage Développeur Web',
                'description' => 'Stage de 6 mois pour apprendre le développement web avec notre équipe.',
                'requirements' => 'Étudiant en informatique, connaissance HTML/CSS/JavaScript',
                'location' => 'Marseille',
                'contractType' => ContractTypeEnum::STAGE,
                'remoteType' => RemoteTypeEnum::ONSITE,
                'salaryMin' => 600,
                'salaryMax' => 1000,
            ],
            [
                'title' => 'Alternance Développeur Mobile',
                'description' => 'Alternance d\'un an pour développer des applications mobiles iOS et Android.',
                'requirements' => 'Flutter ou React Native, étudiant en Master',
                'location' => 'Bordeaux',
                'contractType' => ContractTypeEnum::ALTERNANCE,
                'remoteType' => RemoteTypeEnum::HYBRID,
                'salaryMin' => 1200,
                'salaryMax' => 1500,
            ],
            [
                'title' => 'Développeur Backend Senior',
                'description' => 'Poste de développeur senior pour architecturer nos microservices.',
                'requirements' => 'Java ou PHP, Docker, Kubernetes, 5+ ans d\'expérience',
                'location' => 'Paris',
                'contractType' => ContractTypeEnum::CDI,
                'remoteType' => RemoteTypeEnum::REMOTE,
                'salaryMin' => 55000,
                'salaryMax' => 70000,
            ],
            [
                'title' => 'CDD Développeur Frontend',
                'description' => 'Mission de 6 mois pour refonte de notre interface utilisateur.',
                'requirements' => 'Vue.js ou React, CSS avancé, UX/UI',
                'location' => 'Lyon',
                'contractType' => ContractTypeEnum::CDD,
                'remoteType' => RemoteTypeEnum::HYBRID,
                'salaryMin' => 38000,
                'salaryMax' => 48000,
            ],
            [
                'title' => 'DevOps Engineer',
                'description' => 'Rejoignez notre équipe pour gérer notre infrastructure cloud.',
                'requirements' => 'AWS ou Azure, Terraform, CI/CD, Docker',
                'location' => 'Paris',
                'contractType' => ContractTypeEnum::CDI,
                'remoteType' => RemoteTypeEnum::REMOTE,
                'salaryMin' => 50000,
                'salaryMax' => 65000,
            ],
            [
                'title' => 'Tech Lead PHP (BROUILLON)',
                'description' => 'Offre en cours de rédaction...',
                'requirements' => 'À définir',
                'location' => 'Marseille',
                'contractType' => ContractTypeEnum::CDI,
                'remoteType' => RemoteTypeEnum::HYBRID,
                'salaryMin' => null,
                'salaryMax' => null,
            ],
        ];

        // Créer les offres en les associant aux entreprises et recruteurs
        foreach ($jobsData as $index => $data) {
            $jobOffer = new JobOffer();
            $jobOffer->setTitle($data['title']);
            $jobOffer->setDescription($data['description']);
            $jobOffer->setRequirements($data['requirements']);
            $jobOffer->setLocation($data['location']);
            $jobOffer->setContractType($data['contractType']);
            $jobOffer->setRemoteType($data['remoteType']);
            $jobOffer->setSalaryMin($data['salaryMin']);
            $jobOffer->setSalaryMax($data['salaryMax']);

            // Associer à une entreprise (en rotation)
            $company = $companies[$index % count($companies)];
            $jobOffer->setCompany($company);

            // Associer à un recruteur (en rotation)
            $recruiter = $users['recruiters'][$index % count($users['recruiters'])];
            $jobOffer->setUser($recruiter);

            $manager->persist($jobOffer);
            $jobOffers[] = $jobOffer;
        }

        // ========================================
        // 4. CRÉER DES CANDIDATURES
        // ========================================

        $applicationMessages = [
            "Bonjour, je suis très intéressé par cette offre. Mon profil correspond parfaitement aux exigences.",
            "Je postule à cette offre car elle correspond à mes compétences et aspirations professionnelles.",
            "Passionné par le développement web, je serais ravi de rejoindre votre équipe.",
            "Mon expérience en développement et ma motivation font de moi le candidat idéal.",
            "Je souhaite mettre mes compétences au service de votre entreprise.",
        ];

        // Créer des candidatures aléatoires
        // Chaque candidat postule à 2-3 offres
        foreach ($users['candidates'] as $candidate) {
            // Nombre aléatoire de candidatures (2 à 3)
            $numApplications = rand(2, 3);

            // Sélectionner des offres aléatoires
            $selectedJobs = array_rand($jobOffers, min($numApplications, count($jobOffers)));

            if (!is_array($selectedJobs)) {
                $selectedJobs = [$selectedJobs];
            }

            foreach ($selectedJobs as $jobIndex) {
                $jobOffer = $jobOffers[$jobIndex];

                $application = new Application();
                $application->setJobOffer($jobOffer);
                $application->setUser($candidate);
                $application->setMessage($applicationMessages[array_rand($applicationMessages)]);
                $application->setResumeUrl("https://example.com/cv/{$candidate->getEmail()}.pdf");

                // Statut aléatoire
                $statuses = [
                    ApplicationStatusEnum::PENDING,
                    ApplicationStatusEnum::PENDING,
                    ApplicationStatusEnum::PENDING, // Plus de chances d'être en attente
                    ApplicationStatusEnum::ACCEPTED,
                    ApplicationStatusEnum::REJECTED,
                ];
                $application->setStatus($statuses[array_rand($statuses)]);

                $manager->persist($application);

                // Incrémenter le compteur de candidatures de l'offre
                $jobOffer->incrementApplicationsCount();
            }
        }

        // ========================================
        // 5. SAUVEGARDER TOUT EN BASE
        // ========================================

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "   - " . count($users['candidates']) . " candidats\n";
        echo "   - " . count($users['recruiters']) . " recruteurs\n";
        echo "   - 1 admin\n";
        echo "   - " . count($companies) . " entreprises\n";
        echo "   - " . count($jobOffers) . " offres d'emploi\n";
        echo "   - Plusieurs candidatures créées\n\n";

        echo "📧 Comptes de test :\n";
        echo "   Candidat : candidate1@example.com / password123\n";
        echo "   Recruteur : recruiter1@example.com / password123\n";
        echo "   Admin : admin@example.com / admin123\n\n";
    }
}