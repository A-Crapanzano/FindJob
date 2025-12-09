<?php

namespace App\Entity;

use App\Enum\ApplicationStatusEnum;
use App\Repository\ApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
#[ORM\UniqueConstraint(name: 'unique_job_user', columns: ['job_offer_id', 'user_id'])]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JobOffer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false, name: 'job_offer_id')]
    private JobOffer $jobOffer;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $resumeUrl = null;

    #[ORM\Column(type: 'string', enumType: ApplicationStatusEnum::class)]
    private ApplicationStatusEnum $status = ApplicationStatusEnum::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $appliedAt;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $resumeKey = null;

    public function __construct()
    {
        $this->appliedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobOffer(): JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobOffer $jobOffer): self
    {
        $this->jobOffer = $jobOffer;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getResumeUrl(): ?string
    {
        return $this->resumeUrl;
    }

    public function setResumeUrl(?string $resumeUrl): self
    {
        $this->resumeUrl = $resumeUrl;
        return $this;
    }

    public function getStatus(): ApplicationStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAppliedAt(): \DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(\DateTimeImmutable $appliedAt): self
    {
        $this->appliedAt = $appliedAt;
        return $this;
    }

    public function getResumeKey(): ?string
    {
        return $this->resumeKey;
    }

    public function setResumeKey(?string $resumeKey): static
    {
        $this->resumeKey = $resumeKey;

        return $this;
    }
}
