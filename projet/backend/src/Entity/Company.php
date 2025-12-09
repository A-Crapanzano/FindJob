<?php

namespace App\Entity;

use App\Enum\CompanySizeEnum;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'companies')]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'string', enumType: CompanySizeEnum::class, nullable: true)]
    private ?CompanySizeEnum $size = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, JobOffer>
     */
    #[ORM\OneToMany(targetEntity: JobOffer::class, mappedBy: 'company', orphanRemoval: true)]
    private Collection $jobOffers;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'company')]
    private Collection $recruiters;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->jobOffers = new ArrayCollection();
        $this->recruiters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
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

    public function getSize(): ?CompanySizeEnum
    {
        return $this->size;
    }

    public function setSize(?CompanySizeEnum $size): self
    {
        $this->size = $size;
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
     * @return Collection<int, JobOffer>
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function addJobOffer(JobOffer $jobOffer): self
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->add($jobOffer);
            $jobOffer->setCompany($this);
        }
        return $this;
    }

    public function removeJobOffer(JobOffer $jobOffer): self
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            if ($jobOffer->getCompany() === $this) {
                $jobOffer->setCompany(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRecruiters(): Collection
    {
        return $this->recruiters;
    }

    public function addRecruiter(User $recruiter): static
    {
        if (!$this->recruiters->contains($recruiter)) {
            $this->recruiters->add($recruiter);
            $recruiter->setCompany($this);
        }

        return $this;
    }

    public function removeRecruiter(User $recruiter): static
    {
        if ($this->recruiters->removeElement($recruiter)) {
            // set the owning side to null (unless already changed)
            if ($recruiter->getCompany() === $this) {
                $recruiter->setCompany(null);
            }
        }

        return $this;
    }
}
