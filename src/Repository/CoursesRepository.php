<?php

namespace App\Repository;

use App\Entity\Courses;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Courses|null find($id, $lockMode = null, $lockVersion = null)
 * @method Courses|null findOneBy(array $criteria, array $orderBy = null)
 * @method Courses[]    findAll()
 * @method Courses[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CoursesRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Courses::class);
    }

    /**
     * Get course related files
     * @param $courseId
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getResourceFiles($courseId)
    {
        $conn=$this->getEntityManager()->getConnection();

        $sql='
        select f.id,f.file_name,f.mime_type,f.size,f.file_tag_id,f.updated_at
        from file as f
        inner join course_resource as cr
        on cr.file_id=f.id
        where cr.course_id=:course_id';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['course_id'=>$courseId]);
        return $stmt->fetchAll();

    }

    /**
     * Get course information for admin backend
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllCourseInfo(){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        select c.id,c.name,u.username as teacher_name,
        c.course_hour,c.info,count(sc.courses_id) as sc_num,c.capacity,c.created_at,cc.category_id
         from courses as c
         inner join user as u on c.teacher_id=u.id
         left join student_course as sc
         on sc.courses_id =c.id
         left join courses_category as cc 
         on c.id=cc.courses_id
         group by c.id
        ';
        $stmt= $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();

    }

    /**
     * Calculate  number of students that selected the course
     * @param $courseId
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSelectedStudentCount($courseId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        select count(sc.courses_id) as sc_num
         from courses as c
         left join student_course as sc
         on sc.courses_id =c.id
         where c.id=:course_id
         group by c.id
        ';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['course_id'=>$courseId]);
        return $stmt->fetchAll();
    }

    /**
     * To judge if a user has selected the course
     * @param $userId
     * @param $courseId
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isSelected($userId,$courseId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        select * from 
        student_course as sc
        where sc.user_id =:userId and sc.courses_id=:courseId
       
        ';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['courseId'=>$courseId,'userId'=>$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete course related tags.
     * @param $courseId
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteCourseRelatedTags($courseId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        delete courses,tags from courses
        left join tags on courses.id=tags.course_id
        where courses.id=:courseId       
        ';
        $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
        $stmt= $conn->prepare($sql);
        $stmt->execute(['courseId'=>$courseId]);
        return $stmt->rowCount();
    }

    /**
     * Delete course resource files.
     * @param $courseId
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteCourseResourceFiles($courseId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        delete course_resource,file from course_resource
        inner join file on course_resource.file_id=file.id
        where course_resource.course_id=:courseId       
        ';
        $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
        $stmt= $conn->prepare($sql);
        $stmt->execute(['courseId'=>$courseId]);
        return $stmt->rowCount();
    }

    /**
     * Delete course category.
     * @param $courseId
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteCourseCategory($courseId){
        $conn=$this->getEntityManager()->getConnection();
        $sql='
        delete  from courses_category
        where courses_id=:courseId       
        ';
        $stmt= $conn->prepare($sql);
        $stmt->execute(['courseId'=>$courseId]);
        return $stmt->rowCount();
    }

    public function findByFuzzyQuery($rawQuery)
    {
        $query=$this->sanitizeSearchQuery($rawQuery);
        $query=$this->extractSearchTerms($query);

        if($this->count($query)==0){
            return [];
        }

        $queryBuilder=$this->createQueryBuilder('p');

        foreach ($query as $key => $term){
            $queryBuilder
                ->orWhere('p.name LIKE :t_'.$key)
                ->setParameter('t_'.$key, '%'.$term.'%');
        }

        return $queryBuilder
            ->orWhere('p.publisheAt','ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Removes all non-alphanumeric characters except whitespaces.
     *
     * @param $query
     *
     * @return string
     */
    public function sanitizeSearchQuery($query)
    {
        $string=trim(preg_replace('/\s+/',' ',$query));
        return preg_replace('/[^[:alpha:] ]/','',$string);
    }
    /**
     * Splits the search query into terms and removes the ones which are irrelevant.
     *
     * @param string $searchQuery
     *
     * @return array
     */
    public function extractSearchTerms($query)
    {
        $terms = array_unique(explode(' ',$query));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }
    public function removeCourse($course){
        $students=$course->getStudents();
        foreach ($students as $student){
            $course->removeStudent($student);
        }
        $files=$course->getResourceFiles();
        foreach ($files as $file){
            $course->removeResourceFiles($file);
        }
        $tasks=$course->getTasks();
        foreach ($tasks as $task)
            $course->remove($task);
        $categorys=$course->getCategory();
        foreach ($categorys as $category) {
            $category->removeCourse($course);
            $course->removeCategory($category);
        }
        $local_fs=new \Symfony\Component\Filesystem\Filesystem();
        $filename = $course->getCoverImg()->getFileName();
        $local_fs->remove(array('/img', $filename));
    }

    // /**
    //  * @return Courses[] Returns an array of Courses objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Courses
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
