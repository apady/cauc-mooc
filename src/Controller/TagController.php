<?php
namespace  App\Controller;

use App\Entity\Tags;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use BFS\FileSystem;
use BFS\Exception\IOExceptionInterface;
/**
 * @Route("/tag", name="tag")
 *
 */
class TagController extends BaseController
{
    /**
     * Get course related tags
     * @Route("/{courseId}", name="Get",condition="context.getMethod() in ['GET']")
     *
     */
    public function getAllTagAction($courseId)
    {
        /**
         * @var $repo \App\Repository\TagsRepository
         */
        $repo = $this->getDoctrine()->getRepository('App:Tags');
        $tags=$repo->getCourseRelatedTags($courseId);
        $response=new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->setData($tags);
        return $response;

    }

    /**
     * Add course related tag
     * @IsGranted("ROLE_TEACHER")
     * @Route("/add/{courseId}", name="Add",condition="context.getMethod() in ['POST']")
     *
     */
    public function addTagAction(Request $request,$courseId)
    {
        /**
         * @var $repo \App\Repository\CoursesRepository
         */
        $repo = $this->getDoctrine()->getRepository('App:Courses');
        $course=$repo->find($courseId);
        $tag=new Tags();
        $tag->setTag($request->request->get('name'));
        $tag->setCourse($course);
        $em=$this->getDoctrine()->getManager();
        $em->persist($tag);
        $em->flush();
        return new JsonResponse(['success'=>true]);

    }

    /**
     * Delete a tag
     * @IsGranted("ROLE_TEACHER")
     * @Route("/delete",name="Delete",condition="context.getMethod() in ['DELETE']")
     * @return JsonResponse
     *
     */
    public function deleteTagAction(Request $request)
    {
        /**
         * @var $repo \App\Repository\TagsRepository
         */

        $tagId=$request->get('id');
        $repo = $this->getDoctrine()->getRepository('App:Tags');
        $em=$this->getDoctrine()->getManager();
        $tag=$repo->find($request->get('id'));

        try {
            $bfs=new FileSystem($this->getParameter('bfs_flag'));
            $files=$repo->getTagRelatedFiles($tagId);
            foreach ($files as $file){
                $bfs->remove($file['path']);
            }
            $repo->deleteTagRelatedFiles($request->get('id'));
            $em->remove($tag);
            $em->flush();

        } catch (IOExceptionInterface $exception) {
            return new JsonResponse(['success'=>false,$exception->getMessage()],500);
        }


        return new JsonResponse(['success'=>true]);
    }

    /**
     * Set file's tag
     * @IsGranted("ROLE_TEACHER")
     * @Route("/set/{tagId}/{fileId}", name="SetFile",condition="context.getMethod() in ['GET']")
     * @throws DBALException
     */
    public function setFileTagAction($tagId,$fileId)
    {
        /**
         * @var $repo \App\Repository\TagsRepository
         */
        $repo = $this->getDoctrine()->getRepository('App:Tags');
        $rowCount=$repo->setFileTag($tagId,$fileId);
        if($rowCount>0){
            return new JsonResponse(['success'=>true]);
        }else{
            return new JsonResponse(['success'=>false]);
        }


    }

    /**
     * Delete a tag
     * @IsGranted("ROLE_TEACHER")
     * @Route("/delete/test/{id}",name="test")
     * @return JsonResponse
     *
     */
    public function deleteTagActionTest($id)
    {
        /**
         * @var $repo \App\Repository\TagsRepository
         */


        $repo = $this->getDoctrine()->getRepository('App:Tags');
        $em=$this->getDoctrine()->getManager();
        $tag=$repo->find($id);

        try {
            $bfs=new FileSystem($this->getParameter('bfs_flag'));
            $files=$repo->getTagRelatedFiles($id);
            foreach ($files as $file){
                $bfs->remove($file['path']);
            }
            $repo->deleteTagRelatedFiles($id);
            $em->remove($tag);
            $em->flush();

        } catch (IOExceptionInterface $exception) {
            return new JsonResponse(['success'=>false,$exception->getMessage()],500);
        }



        return new JsonResponse(['success'=>true]);
    }

}