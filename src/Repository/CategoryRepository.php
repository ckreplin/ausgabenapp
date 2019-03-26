<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends BaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function list()
    {
        return $this
            ->createQueryBuilder('c')
            ->where('c.user = :user')
            ->orWhere('c.shared = true')
            ->setParameter('user', $this->getUser())
            ->addOrderBy('c.shared', 'asc')
            ->addOrderBy('c.income', 'asc')
            ->addOrderBy('c.title', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function findOneById($id)
    {
        return $this
            ->findOneBy(
                [
                    'id' => $id,
                    'user' => $this->getUser()
                ]
            );
    }
}
