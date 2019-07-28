<?php
namespace  App\Controller;



use App\Entity\File;
use App\Repository\CoursesRepository;
use App\Entity\Tags;
use App\Entity\User;
use App\Entity\Courses;
use App\Entity\Category;
use App\Form\Type\UpLoadFileType;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\StringType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormTypeInterface;
use App\Form\Type\TeacherAddCourseType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use BFS\FileSystem;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use BFS\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;






class CourseController extends  BaseController
{
    use TargetPathTrait;
    /**
     * @Route("/course/list", name="courseList")
     *
     */
    public function showAllCourseAction($isShare=0,$encode_string=null)
    {

        $courses=$this->getAllCourseFromDateBase();
        foreach ($courses as $course){

            $category=$course->getCategory();
            $id=$category[0]->getId();
            $course->cateid=$id;
        }
        return $this->render('index.html.twig', array(
            "isShare"=>$isShare,
                "encode_string"=>$encode_string,
                'courseList' => $courses));

    }
    /**
     *@Route("/course/add/submit", name="courseAdd")
     *@IsGranted("ROLE_TEACHER")
     */
    public function addSubmitAction(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $imgUploader='img/';
        $file=$request->files->get('file');
        $fileName= uniqid("img_");
        $pathUploader=$file->getPathname();
        if(!move_uploaded_file($pathUploader,$imgUploader.$fileName))
            throw $this->createNotFoundException('上传失败'.$imgUploader);
        $file=new File();
        $file->setFileName($fileName);
        $file->setSize($request->request->get('size'));
        $file->setPath($imgUploader.$fileName);
        $file->setMimeType($request->request->get('type'));
        $file->setUser($this->getUser());
        $em=$this->getDoctrine()->getManager();
        $em->persist($file);
        $em->flush();
        $course=$this->getCoursesFromDateBase(NULL,$request->request->get('name'),$this->getUser());
        if ($course!=NULL){
            throw $this->createNotFoundException('course is exist');
        }else{
            $course=new Courses();
        }
        $category=$this->getCategoryFromDateBase(NULL,$request->request->get('category'));
        if($category==NULL){
            throw $this->createNotFoundException('categery not exixt');
        }
        $em = $this->getDoctrine()->getManager();
        $course->setName($request->request->get('courseName'));
        $course->setInfo($request->request->get('info'));
        $course->setTeacher($this->getUser());
        $course->setCourseHour($request->request->get('courseHours'));
        $course->setCourseNumber(date("his") + $this->getUser()->getId());
        $course->addCategory($category);
        $course->setCoverImg($file);
        $course->setCapacity($request->request->get('capacity'));
        $em->persist($course);
        $em->flush();
        $path2 = $this->getUser()->getUsername() . $this->getUser()->getId() . "/" .$request->request->get('courseName');
        try {
            $bfs = new FileSystem($this->getParameter('bfs_flag'));
        }catch (IOExceptionInterface $exception){
            return new Response($exception->getMessage());
        }
        try {
            $bfs->mkdir($path2);
            $bfs->mkdir($path2 . "/" . '教学资源');
            $bfs->mkdir($path2 . "/" . '发布作业');
            $bfs->mkdir($path2 . "/" . '学生作业');
            $bfs->mkdir($path2 . "/" . '默认');
        } catch (IOExceptionInterface $exception) {
            return new Response( $exception->getMessage());
        }
        return new JsonResponse(['success'=>true]);
    }

    /**
     * @Route("/course/add",name="courseaddshow")
     *
     */
    public function courseAddShowAction(){

        $categorys=$this->getAllCategoryFromDateBase();
        return $this->render('mine/course.html.twig',array('categorys'=>$categorys));
    }

    /**
     * @Route("/course/added", name="courseAdded")
     * @IsGranted("ROLE_TEACHER")
     */
    public function addedCourseAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $courses = $user->getCoursesToTeach();


        foreach ($courses as $course){

            $category=$course->getCategory();
            $name=$category[0]->getName();
            $course->cate=$name;
        }
        return $this->render('mine/addedcourse.html.twig',
            array(
                'courseList' => $courses
            )
        );
    }
    /**
     * @Route("/course/joined", name="courseJoined")
     * @IsGranted("ROLE_USER")
     */
    public function joinedCourseAction()
    {
        /**
         * @var $user \App\Entity\User
         */
        $user = $this->getUser();
        $courses = $user->getCoursesSelected();


        foreach ($courses as $course){

            $category=$course->getCategory();
            $name=$category[0]->getName();
            $course->cate=$name;
        }

        return $this->render('mine/joinedcourse.html.twig',
            array(
                'courseList' => $courses
            )
        );
    }

    /**
     * @Route("/course/search", name="courseSearch")
     * @IsGranted("ROLE_USER")
     */
    public function searchCourseAction(Request $request)
    {
        if(!$request->isXmlHttpRequest()){
            throw $this->createNotFoundException('error');
        }

        $query = $request->query->get('q');
        $courses= $this->getDoctrine()->getRepository(Courses::class)
            ->findByFuzzyQuery($query);

        $results =[];
        foreach ($courses as $course){
            $results[] = [
                'id'=>htmlentities($course->getId()),
                'name'=>htmlentities($course->getName())
            ]
            ;
        }
        return $this->json($results);
    }
    /**
     * @Route("/course/edit/{id}", name="courseEdit",requirements={"id"="\d+"})
     * @IsGranted("ROLE_TEACHER")
     */
    public function editCourseAction($id)
    {
        $user = $this->getUser();
        $course = $this->getCoursesFromDateBase($id, $user);
        if($course==NULL){
            throw $this->createNotFoundException('course is not exist');
        }
        $category=$course->getCategory()[0];
        $categorys=$this->getAllCategoryFromDateBase();

        $course=new Courses();

        return new JsonResponse([
            'courseName'=>$course->getName(),
            'courseInfo'=>$course->getInfo(),
            'courseHours'=>$course->getCourseHour(),
             'category'=>$category,
            'categorys'=>$categorys,
            'courseImgPath'=>$course->getCoverImg()
            ]);
    }
    /**
     * @Route("/course/search/submit", name="courseSearchSubmit")
     * @IsGranted("ROLE_USER")
     */
    public function searchCourseSubmitAction(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user=$this->getUser();

        $coursename=$request->request->get('coursename');
        $course=$this->getCoursesFromDateBase(NULL,$coursename,$user);
        if($course==NULL)
            throw $this->createNotFoundException('The course does not exist');

        return $this->redirectToRoute('courseEdit', array(
            'id' => $course->getId()
        ));
    }
    /**
     * @Route("/course/edit/{id}/update", name="courseEditUpdate",requirements={"id"="\d+"})
     * @IsGranted("ROLE_TEACHER")
     */
    public function editCourseUpdateAction($id, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $course=$this->getDoctrine()->getRepository('App:Courses')->find($id);
        $oldFilePath=$course->getCoverImg()->getPath();

        $user=$this->getUser();
        if($course->getTeacher()!=$user){
            $response=new JsonResponse();
            $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            $response->setData(['success'=>false,'message'=>'This course does not belong to you.']);
            $response->setStatusCode(403);
            return $response;
        }

        try {
            $local_bfs=new FileSystem($this->getParameter('bfs_flag'));

        }catch (IOExceptionInterface $exception){
            return new Response($exception->getMessage());
        }
        if (strcmp($request->request->get('name'),$course->getName()))
            $local_bfs->rename("/".$this->getUser()->getUsername() . $this->getUser()->getId() . "/" .$course->getName()
                ,"/".$this->getUser()->getUsername() . $this->getUser()->getId() . "/" .$request->request->get('name'));
        $category=$this->getCategoryFromDateBase(NULL,$request->request->get('category'));
        $categorys=$course->getCategory();
        foreach ($categorys as $i)
            $course->removeCategory($i);

        $course->setName($request->request->get('name'));
        $course->setInfo($request->request->get('info'));
        $course->setCourseHour($request->request->get('courseHours'));
        $course->addCategory($category);
        $course->setCapacity($request->request->get('num'));

        $category->addCourse($course);

        $em=$this->getDoctrine()->getManager();
        $em->flush();

        return new JsonResponse(['status'=>200]);

        }
     /**
      * @Route("/course/edit/{id}/delete", name="courseEditDelete",requirements={"id"="\d+"})
      * @IsGranted("ROLE_TEACHER")
      * @throws /DBALException
      */
     public function editCourseDeleteAction($id)
     {
         $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
         $user=$this->getUser();


         /**
          * @var $repo \App\Repository\CoursesRepository
          */
         $repo=$this->getDoctrine()->getRepository('App:Courses');

         /**
          * @var $course \App\Entity\Courses
          */
         $course=$repo->find($id);
         if($course->getTeacher()!=$user){
             $response=new JsonResponse();
             $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
             $response->setData(['success'=>false,'message'=>'This course does not belong to you.']);
             $response->setStatusCode(403);
             return $response;
         }


         $em = $this->getDoctrine()->getManager();

         $filename = $course->getCoverImg()->getFileName();
         $repo->deleteCourseRelatedTags($id);
         $repo->deleteCourseResourceFiles($id);
         $repo->deleteCourseCategory($id);
         $fs=new FileSystem($this->getParameter('bfs_flag'));
         $local_fs=new \Symfony\Component\Filesystem\Filesystem();

         $local_fs->remove(array('/img', $filename));

         $path = $user->getUsername() . $user->getId() . "/" . $course->getName();

         try {
             $fs->rmdir($path);
             $em->remove($course);
             $em->flush();

         } catch (IOExceptionInterface $exception) {
             return new JsonResponse($exception->getMessage());
         }
         return new JsonResponse(['status'=>200]);

     }
    /**
     *
     * @Route("/course/{id}", name="oneCourseInfo")
     * @throws /DBALException
     *
     */
    public function showOneCourseAction($id)
    {
        /**
         * @var $repo \App\Repository\CoursesRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:Courses');
        $course=$repo->find($id);
        if($course==NULL)
            throw $this->createNotFoundException('The course does not exist');
        $isSelected=false;
        if($this->getUser()!=null)
            $isSelected=count($repo->isSelected($this->getUser()->getId(),$course->getId()))==1?true:false;




            $category=$course->getCategory();
            $name=$category[0]->getName();
            $course->cate=$name;


        $courseTags=$course->getTags();
        $selectedStudentCount=$repo->getSelectedStudentCount($id);

        return $this->render('course.html.twig', array(

                "course" => $course,
                "tags"=>$courseTags,
                "selectedStudentCount"=>$selectedStudentCount[0]['sc_num'],
                "isSelected"=>$isSelected

            )
        );
    }
    /**
     *
     * @Route("/course/{id}/join", name="oneCourseJoin",requirements={"id"="\d+"})
     */
    public function oneCourseJoinAction($id,Request $request){
        if ($this->getUser()==NULL) {
            $this->saveTargetPath($request->getSession(), 'main', $request->getUri());
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        }
        else {
            $course = $this->getCoursesFromDateBase($id);
            $course->addStudent($this->getUser());
            $em = $this->getDoctrine()->getManager();
            $em->persist($course);
            $em->flush();
            return new JsonResponse(["status"=>200]);
        }
    }
    /**
     *
     * @Route("/course/{id}/get-resource-files", name="getResourceFiles",requirements={"id"="\d+"})
     *
     */
    public function getResourceFilesAction($id){
        function formatFileSize($fileSize) {
            if ($fileSize < 1024) {
                return $fileSize.'B';
            } else if ($fileSize < (1024*1024)) {
                $temp = $fileSize / 1024;
                $temp = number_format($temp,2);
                return $temp.'KB';
            } else if ($fileSize < (1024*1024*1024)) {
                $temp = $fileSize / (1024*1024);
                $temp = number_format($temp,2);
                return $temp.'MB';
            } else {
                $temp = $fileSize / (1024*1024*1024);
                $temp = number_format($temp,2);
                return $temp.'GB';
            }
        }
        /**
         * @var $repo \App\Repository\CoursesRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:Courses');
        $files=$repo->getResourceFiles($id);
        foreach ($files as &$file){
            $file_name=$file['file_name'];
            $file_type=strtoupper(substr($file_name,strrpos($file_name,'.')+1));
            $file['type']=$file_type;
            $file['size']=formatFileSize($file['size']);
        }

        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($files);
        return $response;
    }
}