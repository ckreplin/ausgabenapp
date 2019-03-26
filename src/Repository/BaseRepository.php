<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BaseRepository extends ServiceEntityRepository
{
    /** @var User */
    private $user;

    /**
     * @param string $entityClass The class name of the entity this repository manages
     */
    public function __construct(RegistryInterface $registry, $entityClass = '')
    {
        parent::__construct($registry, $entityClass);
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }
}
