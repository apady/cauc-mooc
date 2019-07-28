<?php

namespace App\Controller;

use App\Entity\Courses;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\Type\UserLoginType;
use App\Form\Type\UserRegisterType;

use App\Service\RedisService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use BFS\FileSystem;
use BFS\Exception\IOExceptionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\SMS\RegisterCodeSender;
use Symfony\Component\Cache\Adapter\RedisAdapter;




class DefaultController extends BaseController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {


        return $this->redirectToRoute('courseList');
    }


    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {


    }

    /**
     * @Route("/register",name="register")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {

        return $this->redirectToRoute('login');
//        }

//        return $this->render('register.html.twig', array(
//
//            'loginStatus'=>$this->checkUserStatus($request),
//        ));
    }
    /**
     * @Route("/register/submit",name="register-submit")
     */
    public function registerSubmitAction(Request $request, UserPasswordEncoderInterface $passwordEncoder,RedisService $redis)
    {
        $user = new User();
        $UserProfile = new UserProfile();
        $user->setUsername($request->get("username"))
            ->setPlainPassword($request->get("password"));

        $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);
        $UserProfile->setRegistrayionIP($request->getClientIp())
            ->setLoginIP($request->getClientIp())
            ->setLastLogin(new \DateTime('now'));
        //验证码检查
        if($request->get("code")===$redis->getRedisClient()->get($request->get("mobile"))){
            $UserProfile->setMobile($request->get("mobile"));
        }
        else{return new Response("1");}
        $user->setProfile($UserProfile);
        /**
         * TODO List
         * 为方便测试，默认赋予管理员和教师权限
         * 投入使用前需要做修改
         **/
        $user->setRoles(['ROLE_TEACHER','ROLE_ADMIN']);
        $user->setIsActivated(true);
        $em=$this->getDoctrine()->getManager();
        $em->persist($user);
        $em->persist($UserProfile);
        $em->flush();
        return new Response("2");
    }
    /**
     * @Route("/check/username",name="check-username")
     */
    public function checkUsernameAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $repo=$this->getDoctrine()->getRepository('App:User');
            if ($repo->findBy(['username'=>$request->get('username')]))
                return new Response(1);
            return new Response(0);
        }
    }
    /**
     * @Route("/check/mobile",name="check-mobile")
     */
    public function checkMobileAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $repo=$this->getDoctrine()->getRepository('App:UserProfile');
            if ($repo->findBy(['mobile'=>$request->get('mobile')]))
                return new Response(1);
            return new Response(0);
        }
    }
    /**
     * @Route("/register/code-sender",name="code-sender")
     */
    public function codeSenderAction(Request $request,RedisService $redis,RegisterCodeSender $sender)
    {
        $mobile=$request->get("phone");
        $randcode=rand(100000,999999);
        $sender->setPhoneNumber($mobile)
            ->setCode($randcode)
            ->sendSms();
        $redis->getRedisClient()->set($mobile,$randcode,300);

        return new Response("发送到".$mobile."成功");

    }
    /**
     * @Route("/verify/{token}",name="verify")
     */
    public  function verifyAction($token)
    {
        /**
         * @var $user \App\Entity\User
         */

        $repo=$this->getUserRepository();
        $user=$repo->findOneBy(array('token'=>$token));
        if($token==$user->getToken()){
            $user->setIsActivated(true);
            $em=$this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('login');
        }
        return new Response('激活失败');

    }
//    /**
//     * @Route("/login/{id}",name="login")
//     */
//    public function loginAction(Request $request,UserPasswordEncoderInterface $passwordEncoder,$id=0)
//    {
//        if($this->checkUserStatus2($request)==1){
//            $request->getSession()->clear();
//            $request->getSession()->invalidate();
//        }
//
//        $user = new User();
//
//        $form=$this->createForm(UserLoginType::class,$user);
//        $form->handleRequest($request);
//
//
//        if ($form->isSubmitted() && $form->isValid()) {
//
//            $repo = $this->getUserRepository();
//            $plainPassword = $user->getPlainPassword();
//            /**
//             * @var $repo \App\Entity\UserRepository
//             */
//            $user = $repo->findOneBy(['username' => $user->getUsername()]);
//            /**
//             * @var $user \App\Entity\User
//             */
//            $reurl=$request->getSession()->get('Reurl');
//
//            if ($user) {
//                if($user->getIsActive()==0){
//                    $this->addFlash('notice','您的账户还未激活');
//                    return $this->render('login.html.twig', array(
//                        'form' => $form->createView()
//                    ));
//                }
//
//                if ($passwordEncoder->isPasswordValid($user, $plainPassword)) {
//                    $request->getSession()->set('username', $user->getUsername());
//                    $request->getSession()->getMetadataBag()->getLifetime();
//                    $request->getSession()->set('userId', $user->getId());
//                    $request->getSession()->set('userIp',$request->getClientIp());
//
//                   if(!$userProfile=$user->getProfile())
//                       $userProfile=new UserProfile();
//
//                    $userProfile->setLastLogin(new \DateTime('now'));
//                    $userProfile->setLoginIP($request->getClientIp());
//                    $userProfile->setUser($user);
//
//
//
//                    $em=$this->getDoctrine()->getManager();
//                    $em->persist($userProfile);
//                    $user->setProfile($userProfile);
//
//                    $em->flush();
//
//                    if ($reurl != NULL) {
//                        return $this->redirect($reurl);
//                    }
//                    return $this->redirectToRoute('courseList');
//                }
//            }
//        }
//        return $this->render('login.html.twig', array(
//            'form' => $form->createView(),
//            'loginStatus'=>$this->checkUserStatus($request),
//        ));
//    }

}
