<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table("user")
 * @ORM\Entity
 * @UniqueEntity("email")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     * @Assert\NotBlank(message="Vous devez saisir un nom d'utilisateur.")
     */
    #[Assert\Length(
        min: 2,
        max: 25,
        minMessage: 'Votre nom d\'utilisateur doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Votre nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères.'
    )]
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    #[Assert\Length(
        min: 8,
        max: 64,
        minMessage: 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Votre mot de passe ne peut pas dépasser {{ limit }} caractères.'
    )]
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\NotBlank(message="Vous devez saisir une adresse email.")
     * @Assert\Email(message="Le format de l'adresse n'est pas correcte.")
     */
    #[Assert\Length(
        min: 8,
        max: 60,
        minMessage: 'Votre e-mail doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Votre e-mail ne peut pas dépasser {{ limit }} caractères.'
    )]
    private $email;

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
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }
}
