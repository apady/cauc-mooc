<?php
namespace  App\Controller;

use phpDocumentor\Reflection\Types\Null_;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\Aes;
use App\Entity\Category;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * @Route("/category", name="category")
 *
 */
class CategoryController  extends  BaseController
{
    /**
     * @Route("/", name="Get",condition="context.getMethod() in ['GET']")
     *
     */
    public function getAllCategoryAction()
    {
        /**
         * @var $repo \App\Repository\CategoryRepository
         */
        $repo = $this->getDoctrine()->getRepository('App:Category');
        $categories=$repo->getAllCategory();
        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($categories);
        return $response;

    }
    /**
     * Get category related courses.
     * @Route("/course/{categoryId}",name="GetCourse")
     * @return JsonResponse
     */
    public function getCategoryRelatedCourses($categoryId)
    {
        /**
         * @var $repo \App\Repository\CategoryRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:Category');
        $courses=$repo->getCategoryRelatedCourse($categoryId);
        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($courses);
        return $response;

    }

    /**
     * Add an new category
     * @IsGranted("ROLE_TEACHER")
     * @Route("/",name="Add",condition="context.getMethod() in ['POST']")
     * @param Request $request
     * @return JsonResponse
     */
    public function addCategoryAction(Request $request)
    {
        $category=new Category();
        $category->setName($request->request->get('name'));
        $em=$this->getDoctrine()->getManager();
        $em->persist($category);
        $em->flush();
        return new JsonResponse(['success'=>true]);
    }
    /**
     * Delete a category
     * @IsGranted("ROLE_TEACHER")
     * @Route("/",name="Delete",condition="context.getMethod() in ['DELETE']")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteCategoryAction(Request $request)
    {

        $categoryId=$request->get('id');
        /**
         * @var $repo \App\Repository\CategoryRepository
         */
        $repo=$this->getDoctrine()->getRepository('App:Category');
        /**
         * @var $category \App\Entity\Category
         */
        $category=$repo->find($categoryId);
        $em=$this->getDoctrine()->getManager();
        if($repo->hasCourse($categoryId)){
            $response=new JsonResponse();
            $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            $response->setData(['success'=>false,'message'=>'该分类下还有相关课程，不允许删除']);
            $response->setStatusCode(403);
            return $response;
        }
        else{
            $em->remove($category);
            $em->flush();
            return new JsonResponse(['success'=>true]);
        }

    }



}