<?php
 namespace App\Controller;

 use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
 use Symfony\Component\Routing\Annotation\Route;

 /**
  * @Route("/user", name="user")
  * @IsGranted("ROLE_USER")
  */
 class UserController extends BaseController{
     /**
      * @Route("/info", name="Info")
      *
      */
     public function userInfoAction()
     {

         return $this->render('mine/userinfo.html.twig'
         );
     }
 }