<?php

namespace App\Repository;

use App\Entity\Tags;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Tags|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tags|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tags[]    findAll()
 * @method Tags[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Tags::class);
    }

    /**
     * Get course related tags.
     * @param $courseId
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCourseRelatedTags($courseId)
    {
        $conn=$this->getEntityManager()->getConnection();

        $sql='
        select t.course_id,t.tag,t.id as tag_id
        from tags as t
        where t.course_id=:course_id';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['course_id'=>$courseId]);
        return $stmt->fetchAll();

    }

    /**
     * set file's tag.
     * @param $tagId
     * @param $fileId
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setFileTag($tagId,$fileId)
    {
        $conn=$this->getEntityManager()->getConnection();

        $sql='
        update file as f
        set f.file_tag_id=:tagId
        where f.id=:fileId';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['tagId'=>$tagId,'fileId'=>$fileId]);
        return $stmt->rowCount();

    }

    /**
     * Delete files related to given tag.
     * @param $tagId
     * @return mixed
     */
    public function deleteTagRelatedFiles($tagId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        delete course_resource ,file from 
		file left join course_resource
        on  course_resource.file_id=file.id
        where file_tag_id=:tagId';
        $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
        $stmt= $conn->prepare($sql);
        $stmt->execute(['tagId'=>$tagId]);
        return $stmt->rowCount();

    }

    public function getTagRelatedFiles($tagId){
        $conn=$this->getEntityManager()->getConnection();

        $sql='
        select id, path, file_name 
        from file
        where file_tag_id=:tagId';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['tagId'=>$tagId]);
        return $stmt->fetchAll();
    }
    // /**
    //  * @return Tags[] Returns an array of Tags objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Tags
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
