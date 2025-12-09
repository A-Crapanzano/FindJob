<?php

namespace App\Entity;

use App\Enum\ContractTypeEnum;
use App\Enum\RemoteTypeEnum;
use App\Repository\JobOfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\Table(name: 'jobs_offers')]
class JobOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'string', enumType: ContractTypeEnum::class, nullable: true)]
    private ?ContractTypeEnum $contractType = null;

    #[ORM\Column(type: 'string', enumType: RemoteTypeEnum::class)]
    private RemoteTypeEnum $remoteType = RemoteTypeEnum::ONSITE;

    #[ORM\Column(nullable: true)]
    private ?int $salaryMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $salaryMax = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $requirements = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $applicationsCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Application>
     */
    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'jobOffer', orphanRemoval: true)]
    private Collection $applications;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->applications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getContractType(): ?ContractTypeEnum
    {
        return $this->contractType;
    }

    public function setContractType(?ContractTypeEnum $contractType): self
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function getRemoteType(): RemoteTypeEnum
    {
        return $this->remoteType;
    }

    public function setRemoteType(RemoteTypeEnum $remoteType): self
    {
        $this->remoteType = $remoteType;
        return $this;
    }

    public function getSalaryMin(): ?int
    {
        return $this->salaryMin;
    }

    public function setSalaryMin(?int $salaryMin): self
    {
        $this->salaryMin = $salaryMin;
        return $this;
    }

    public function getSalaryMax(): ?int
    {
        return $this->salaryMax;
    }

    public function setSalaryMax(?int $salaryMax): self
    {
        $this->salaryMax = $salaryMax;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getRequirements(): ?array
    {
        return $this->requirements;
    }

    public function setRequirements(?array $requirements): self
    {
        $this->requirements = $requirements;
        return $this;
    }

    public function getApplicationsCount(): int
    {
        return $this->applicationsCount;
    }

    public function setApplicationsCount(int $applicationsCount): self
    {
        $this->applicationsCount = $applicationsCount;
        return $this;
    }

    public function incrementApplicationsCount(): self
    {
        $this->applicationsCount++;
        return $this;
    }

    public function decrementApplicationsCount(): self
    {
        if ($this->applicationsCount > 0) {
            $this->applicationsCount--;
        }
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Application $application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setJobOffer($this);
        }
        return $this;
    }

    public function removeApplication(Application $application): self
    {
        if ($this->applications->removeElement($application)) {
            if ($application->getJobOffer() === $this) {
                $application->setJobOffer(null);
            }
        }
        return $this;
    }
}
