<?php

namespace App\Controller;

use App\Entity\Courses;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;



class BaseController extends AbstractController
{

    public function getUserFromDateBase($id=NULL,$name){
        $em=$this->getDoctrine()->getManager();
        if ($id!=NULL){
            $user=$em->getRepository('App:User')
                ->find($id);
        }else if($name!=NULL){
            $user=$em->getRepository('App:User')
                ->findOneBy(['name'=>$name]);
        }
        return $user;
    }

    public function getFileFromDateBase($id=NULL,$name=NULL){
        $em=$this->getDoctrine()->getManager();
        if ($id!=NULL){
            $file=$em->getRepository('App:File')
                     ->find($id);
        }else if($name!=NULL){
            $file=$em->getRepository('App:File')
                ->findOneBy(['name'=>$name]);
        }
        return $file;
    }

    public function getTagsRepository(){
        $em=$this->getDoctrine()->getManager();

        return $em->getRepository('App:Tags');
    }
    public function getCategoryFromDateBase($id=NULL,$name=NULL){

        $em = $this->getDoctrine()->getManager();
        if ($id!=NULL){
            $category=$em->getRepository('App:Category')
                ->find($id);
        }else if($name!=NULL){
            $category=$em->getRepository('App:Category')
                ->findOneBy(['name'=>$name]);
        }
        return $category;
    }
    public function getAllCategoryFromDateBase(){
        $em = $this->getDoctrine()->getManager();
        $categorys=$em->getRepository('App:Category')
            ->findAll();
        return $categorys;
    }
    public function getCoursesFromDateBase($id=NULL,$name=NULL,$user=NULL)
    {
        $em = $this->getDoctrine()->getManager();
        if($name!=NULL&&$user!=NULL){
            $course=$em->getRepository('App:Courses')
                ->findOneBy(['name'=>$name,
                    'teacher'=>$user]);
        }else if($id!=NULL&&$user!=NULL){
            $course=$em->getRepository('App:Courses')
                ->findOneBy(['id'=>$id,
                    'teacher'=>$user]);
        }else if($id!=NULL){
            $course=$em->getRepository('App:Courses')
                ->find($id);
        }else if($name!=NULL){
            $course=$em->getRepository('App:Courses')
                ->findOneBy(['name'=>$name]);
        }
        return  $course;
    }
    public function getAllCourseFromDateBase(){
        $em = $this->getDoctrine()->getManager();
        $courses=$em->getRepository('App:Courses')
            ->findAll();
        return $courses;
    }


    public function persistCourse($name,$info,$courseHours,$category,$file=NULL,$teachename=NULL){
        $course=new Courses();
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        try {
            $course->setName($name);
            $course->setInfo($info);
            $course->setTeacherName($teachename);
            $course->setCourseHour($courseHours);
            $course->setTeacher($this->getUser());
            $course->setCourseNumber(date("his") + $this->getUser()->getId());
            $course->addCategory($category);
            $course->setCoverImg($file);

            $em->persist($course);
            $em->flush();

            $category->addCourse($course);
            $em->persist($category);
            $em->flush();
            $em->getConnection()->commit();
        }catch (Exception $e){
            $e->getConnection()->rollback();
        }
    }




}
