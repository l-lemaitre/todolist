<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['username', 'email'])]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username.')]
#[UniqueEntity(fields: ['email'], message: 'Cette valeur est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25, unique: true)]
    #[Assert\NotBlank(message: "Vous devez saisir un nom d'utilisateur.")]
    #[Assert\Length(
        min: 2,
        max: 25,
        minMessage: 'Votre nom d\'utilisateur doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Votre nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $username;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    #[Assert\Length(
        min: 8,
        max: 64,
        minMessage: 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Votre mot de passe ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $password = null;

    #[ORM\Column(length: 60, unique: true)]
    #[Assert\NotBlank(message: "Vous devez saisir une adresse email.")]
    #[Assert\Email(message: "Le format de l'adresse e-mail n'est pas correct.")]
    #[Assert\Length(
        min: 8,
        max: 60,
        minMessage: 'Votre e-mail doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Votre e-mail ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $email = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Task::class, orphanRemoval: true)]
    private Collection $tasks;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registration_date = null;

    public function __construct()
    {
        $this->registration_date = new DateTimeImmutable();
        $this->tasks = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getSalt()
    {
        return null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setUser($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getUser() === $this) {
                $task->setUser(null);
            }
        }

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registration_date;
    }

    public function setRegistrationDate(\DateTimeInterface $registration_date): static
    {
        $this->registration_date = $registration_date;

        return $this;
    }
}
