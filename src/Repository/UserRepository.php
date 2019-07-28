<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function getAllUserInfo()
    {
        return $this->createQueryBuilder('u')
            ->join('u.profile','p')
            ->select('u.username','u.id','u.roles','u.isActivated',
                'u.createdAt','p.email','p.mobile',
                'p.lastLogin','p.registrayionIP','p.loginIP')
            ->getQuery()
            ->getResult();
    }

    public function getOneUserInfo($id)
    {
        return $this->createQueryBuilder('u')
            ->join('u.profile','p')
            ->select('u.username','u.roles','u.isActivated',
                'u.createdAt','p.email','p.mobile',
                'p.lastLogin','p.registrayionIP','p.loginIP')
            ->Where('u.id=:id')
            ->setParameter('id',$id)
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
