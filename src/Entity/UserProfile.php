<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserProfileRepository")
 */
class UserProfile
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $registrayionIP;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastLogin;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $loginIP;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", mappedBy="profile", cascade={"persist", "remove"})
     */
    private $user;

    /**
     * 手机号码
     * @ORM\Column(type="string", length=12)
     */
    private $mobile;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistrayionIP(): ?string
    {
        return $this->registrayionIP;
    }

    public function setRegistrayionIP(string $registrayionIP): self
    {
        $this->registrayionIP = $registrayionIP;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLoginIP(): ?string
    {
        return $this->loginIP;
    }

    public function setLoginIP(string $loginIP): self
    {
        $this->loginIP = $loginIP;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        // set the owning side of the relation if necessary
        if ($this !== $user->getProfile()) {
            $user->setProfile($this);
        }

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

   
}
