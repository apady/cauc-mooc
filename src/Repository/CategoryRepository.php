<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Category::class);
    }


    /**
     * Get all categories.
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllCategory()
    {
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        select c.id,c.name
        from category as c
       ';
        $stmt= $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get category related course.
     * @param $categoryId
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCategoryRelatedCourse($categoryId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        select c.id,c.name,co.name,co.info,f.path as cover_img
        from category as c
        left join courses_category as cc 
        on c.id=cc.category_id
        left join courses as co
        on cc.courses_id=co.id
        inner join file as f 
        on co.cover_img_id=f.id
        where c.id=:categoryId      
       ';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['categoryId'=>$categoryId]);
        return $stmt->fetchAll();

    }
    public function hasCourse($categoryId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        select * from 
        courses_category as cc
        where cc.category_id=:categoryId 
       
        ';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['categoryId'=>$categoryId]);
        return count($stmt->fetchAll())>0;
    }


    /*
    public function findOneBySomeField($value): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
