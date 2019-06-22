<?php
namespace App\Controller;


use BFS\Exception\IOException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use BFS\FileSystem;
use BFS\Exception\IOExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;



/**
 * @Route("/admin", name="admin")
 * @IsGranted("ROLE_ADMIN")
 */

class AdminController extends BaseController {
    /**
     * @Route("/", name="home")
     */
    public function adminAction()
    {

        return $this->render('mine/admin.html.twig');
    }
    /**
     * @Route("/fs", name="fs")
     * @Method("GET")
     * @return Response|JsonResponse
     */
    public function getFileSystemStatusAction(){
        $fs=new FileSystem($this->getParameter('bfs_flag'));

        try{
            $res= $fs->status();
        }catch (IOExceptionInterface $exception){

            return new JsonResponse(['fail'=>$exception->getMessage()]);

        }
        $list=explode("\n",$res);  //分行处理
        $res_list=array();
        $status_list=array();
        $properties=array_values(array_filter(explode(" ",$list[0])));// 提取标题

        for($x=2;$x<count($list)-1;$x++){
            $str_tmp=preg_replace ( "/\s(?=\s)/","\\1", trim($list[$x]));//多空格替换为单空格
            $str_res='';

            while((($pos=strpos($str_tmp,"KB"))||($pos=strpos($str_tmp,"MB"))
                    ||($pos=strpos($str_tmp,"GB"))||($pos=strpos($str_tmp,"TB")))
                &&(substr($str_tmp,$pos-1,1)===" ")){
                $str_tmp=substr_replace($str_tmp,"",$pos-1,1);
                $str_res.=substr($str_tmp,0,$pos+1);
                $str_tmp=substr($str_tmp,$pos+1);

            }//去除KB/MB/GB前的空格
            $str_res.=$str_tmp;
            $raw_data=array_values(explode(" ",$str_res));
            for ($i=1;$i<count($properties)-1;$i++)                //结果填充
                $status_list[$properties[$i]]=$raw_data[$i];
            $res_list[$x-1]=$status_list;

        }

       return new JsonResponse($res_list);

    }
    /**
     * @Route("/user-info",name="userInfo")
     * @Method("GET")
     * @return Response|JsonResponse
     */
    public function userInfoAction(){
        /**
         * @var $repo \App\Repository\UserRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:User');
        $users=$repo->getAllUserInfo();
        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($users);
        return $response;
    }
    /**
     * @Route("/courses-info",name="courseInfo")
     * @Method("GET")
     * @return Response|JsonResponse
     */
    public function courseInfoAction(){
        /**
         * @var $repo \App\Repository\CoursesRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:Courses');
        $course=$repo->getAllCourseInfo();
        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($course);
        return $response;
    }
    /**
     * @Route("/user/role/{id}", name="editUserRole",requirements={"id"="\d+"})
     * @Method("POST")
     */
    public function editUserRoleAction(Request $request,$id=0){
        $attribute=$request->get('role');
        if($id==0)
            throw $this->createNotFoundException('This user do not exit');
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('App:User')
            ->find($id);

        if ($attribute==1)
            $user->setRoles([]);
        elseif ($attribute==2)
            $user->setRoles(['ROLE_TEACHER']);
        elseif ($attribute==3)
            $user->setRoles(['ROLE_TEACHER','ROLE_ADMIN']);

        $em=$this->getDoctrine()->getManager();
        $em->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $userInfo=$repo->getOneUserInfo($id);
        return new JsonResponse($userInfo);
    }
    /**
     * @Route("/user/name/{id}", name="editUsername",requirements={"id"="\d+"})
     * @Method("PUT")
     */
    public function editUserNameAction(Request $request,$id=0){
        $username=$request->request->get('name');
        if($id==0)
            throw $this->createNotFoundException('This user do not exit');
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('App:User')
            ->find($id);
        $user->setUsername($username);

        $em=$this->getDoctrine()->getManager();
        $em->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $userInfo=$repo->getOneUserInfo($id);
        return new JsonResponse($userInfo);
    }
    /**
     * @Route("/user/password/{id}", name="editUserPassword",requirements={"id"="\d+"})
     * @Method("PUT");
     */
    public function editUserPasswordAction(Request $request,$id=0,UserPasswordEncoderInterface $passwordEncoder){
        $password=$request->request->get('password');
        if($id==0)
            throw $this->createNotFoundException('This user do not exit');
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('App:User')
            ->find($id);
        $user->setPlainPassword($password);
        $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);

        $em=$this->getDoctrine()->getManager();
        $em->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $userInfo=$repo->getOneUserInfo($id);
        return new JsonResponse($userInfo);
    }
    /**
     * @Route("/user/activated/{id}", name="editUserActivated",requirements={"id"="\d+"})
     * @Method("PUT");
     */
    public function editUserActivatedAction($id=0){
        if($id==0)
            throw $this->createNotFoundException('This user do not exit');
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('App:User')
            ->find($id);
        $user->setIsActivated(false);

        $em=$this->getDoctrine()->getManager();
        $em->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $userInfo=$repo->getOneUserInfo($id);
        return new JsonResponse($userInfo);
    }
    /**
     * @Route("/user/mobile/{id}", name="editUserMobile",requirements={"id"="\d+"})
     * @Method("PUT");
     */
    public function editUserMobileAction(Request $request,$id=0){
        if($id==0)
            throw $this->createNotFoundException('This user do not exit');
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('App:User')
            ->find($id);
        $userMobile=$request->request->get('mobile');
        $userProfile=$user->getProfile();
        $userProfile->setMobile($userMobile);
        $em=$this->getDoctrine()->getManager();
        $em->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $userInfo=$repo->getOneUserInfo($id);
        return new JsonResponse($userInfo);
    }
    /**
     * @Route("/user/email/{id}", name="editUserEmail",requirements={"id"="\d+"})
     * @Method("PUT");
     */
    public function editUserEmailAction(Request $request,$id=0){
        if($id==0)
            throw $this->createNotFoundException('This user do not exit');
        $em=$this->getDoctrine()->getManager();
        $user=$em->getRepository('App:User')
            ->find($id);
        $userEmail=$request->request->get('email');
        $userProfile=$user->getProfile();
        $userProfile->setEmail($userEmail);

        $em=$this->getDoctrine()->getManager();
        $em->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $userInfo=$repo->getOneUserInfo($id);
        return new JsonResponse($userInfo);
    }
    /**
     * @Route("/addUser", name="addUser")
     * @Method("POST");
     */
    public function addUserAction(Request $request,UserPasswordEncoderInterface $passwordEncoder){
        $roles=array($request->request->get('roles'));
        $user=new User();
        $UserProfile = new UserProfile();
        $UserProfile->setRegistrayionIP($request->getClientIp())
            ->setLoginIP($request->getClientIp())
            ->setLastLogin(new \DateTime('now'));
        $UserProfile->setMobile($request->get("mobile"));
        $user->setUsername($request->get("username"))
            ->setPlainPassword($request->get("password"));
        $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setProfile($UserProfile);
        $user->setPassword($password);
        $user->setRoles($roles);
        $user->setIsActivated(true);
        $em=$this->getDoctrine()->getManager();
        $em->persist($UserProfile);
        $em->persist($user);

        $repo=$this->getDoctrine()->getRepository('App:User');
        $users=$repo->getAllUserInfo();
        return new JsonResponse($users);
    }
    /**
     * @Route("/delete/{id}", name="delete")
     * @Method("DELETE");
     */
    public function deleteAction($id=0)
    {
        $em=$this->getDoctrine();
        $user=$em->getRepository('App:User')
            ->find($id);
        $reCourse=$em->getRepository('App:Courses');
        $reFile=$em->getRepository('App:File');
        $role=$user->getRoles();
        $student_courses=$user->getCoursesSelected();
        foreach ($student_courses as $course) {
            $user->removeCoursesSelected($course);
        }
        $userProfile = $user->getProfile();
        $tasks=$user->getTasks();
        foreach ($tasks as $task){
            $user->removeTask($task);
        }
        $files=$reFile->findAll();
        foreach ($files as $file)
        {
            if($file->getUser()->getId()==$id) {
                $em->getManager()->remove($file);
                $em->getManager()->flush();
            }
        }
        if(in_array('ROLE_TEACHER',$role)) {
            $teacher_courses=$user->getCoursesToTeach();
           foreach ($teacher_courses as $course) {
               $user->removeCoursesToTeach($course);
               $reCourse->removeCourse($course);
               $em->getManager()->remove($course);
               $em->getManager()->flush();
           }
            $path2 = $user->getUsername().$user->getId();
            try {
                $fs = new FileSystem($this->getParameter('bfs_flag'));
                $fs->rmdir($path2);
            } catch (IOExceptionInterface $exception) {
                return new JsonResponse($exception->getMessage());
            }
        }
        $em->getManager()->remove($userProfile);
        $em->getManager()->remove($user);
        $em->getManager()->flush();

        $repo=$this->getDoctrine()->getRepository('App:User');
        $users=$repo->getAllUserInfo();
        return new JsonResponse($users);
    }
}