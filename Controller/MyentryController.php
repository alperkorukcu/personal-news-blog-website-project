<?php

namespace App\Controller;

use App\Entity\Admin\News;
use App\Entity\Admin\Image;
use App\Form\Admin\NewsType;
use App\Form\Admin\ImageType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\NewsRepository;
use App\Repository\Admin\SettingsRepository;
use App\Repository\Admin\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


/**
 * @Route("/myentry")
 */
class MyentryController extends AbstractController
{
    /**
     * @Route("/", name="myentry_index", methods="GET")
     */
    public function index(SettingsRepository $settingsRepository, NewsRepository $newsRepository, UserInterface $user): Response
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();
        $userid = $user->getId();
        $news = $newsRepository->findBy(['user_id' => $userid]);

        return $this->render('myentry/index.html.twig', [
            'controller_name' => 'MyentryController',
            'data' => $data,
            'cats' => $cats,
            'news' => $news,
        ]);
    }

    /**
     * @Route("/{id}", name="myentry_show", methods="GET")
     */
    public function show(SettingsRepository $settingsRepository, News $news): Response
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        return $this->render('myentry/show.html.twig', [
            'news' => $news,
            'data' => $data,
            'cats' => $cats,
        ]);
    }

    /**
     * @Route("/new/1", name="myentry_new", methods="GET|POST")
     */
    public function new(SettingsRepository $settingsRepository, Request $request, CategoryRepository $categoryrepository, UserInterface $user): Response
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        $catlist = $categoryrepository->findAll();
        $catname = $categoryrepository->findBy(['id' => 0]);

        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        $userid = $user->getId();

        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();

            return $this->redirectToRoute('myentry_index');
        }

        return $this->render('myentry/new.html.twig', [
            'news' => $news,
            'catlist' => $catlist,
            'catname' => $catname,
            'form' => $form->createView(),
            'data' => $data,
            'cats' => $cats,
            'userid' => $userid,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="myentry_edit", methods="GET|POST")
     */
    public function edit(SettingsRepository $settingsRepository, Request $request, News $news, CategoryRepository $categoryrepository): Response
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        $catlist = $categoryrepository->findAll();
        $catname = $categoryrepository->findBy(['id' => $news->getCategoryId()]);

        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Successfully Saved!');
            return $this->redirectToRoute('myentry_edit', ['id' => $news->getId()]);
        }

        return $this->render('myentry/edit.html.twig', [
            'news' => $news,
            'catlist' => $catlist,
            'catname' => $catname,
            'form' => $form->createView(),
            'data' => $data,
            'cats' => $cats,
        ]);
    }

    /**
     * @Route("/{id}/iedit", name="myentry_iedit", methods="GET|POST")
     */
    public function iedit(Request $request, $id, News $news): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('myentry_edit', ['id' => $news->getId()]);
        }

        return $this->render('myentry/image_edit.html.twig', [
            'news' => $news,
            'id' => $id,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/iupdate", name="myentry_iupdate", methods="POST")
     */
    public function iupdate(Request $request, $id, News $news): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);
        $file = $request->files->get('imagename');
        $filename = $this->generateUniqueFileName().'.'.$file->guessExtension();
        try{
            $file->move(
                $this->getParameter('images_directory'), //services.yaml de tanımlanan image folderımız
                $filename
            );
        }
        catch(FileException $e){
            //...
        }
        $news->setImage($filename);

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('myentry_iedit', ['id' => $news->getId()]);

    }

    /**
     * @Route("/newgallery/{pid}", name="myentry_image_new", methods="GET|POST")
     */
    public function newgallery(Request $request, $pid, ImageRepository $imageRepository): Response
    {
        $imagelist = $imageRepository->findBy(
            ['news_id' => $pid]
        );

        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if($request->files->get('imagename'))
            //if ($form->isSubmitted())
        {
            $file = $request->files->get('imagename');
            $filename = $this->generateUniqueFileName().'.'.$file->guessExtension();
            try{
                $file->move(
                    $this->getParameter('images_directory'),
                    $filename
                );
            }
            catch(FileException $e){
                //...
            }
            $image->setImage($filename);
            $image->setNewsId($pid);

            $em = $this->getDoctrine()->getManager();
            $em->persist($image);
            $em->flush();

            return $this->redirectToRoute('myentry_image_new', array('pid' => $pid));
        }

        return $this->render('myentry/newgallery.html.twig', [
            'image' => $image,
            'imagelist' => $imagelist,
            'pid' => $pid,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="myentry_delete", methods="DELETE")
     */
    public function delete(Request $request, News $news): Response
    {
        if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($news);
            $em->flush();
        }

        return $this->redirectToRoute('myentry_index');
    }

    /**
     * @Route("/{id}/{pid}", name="myentry_image_delete", methods="GET")
     */
    public function imagedelete(Request $request, Image $image, $pid): Response
    {

        $em = $this->getDoctrine()->getManager();
        $em->remove($image);
        $em->flush();


        return $this->redirectToRoute('admin_image_new', array('pid' => $pid));
    }

    //Recursive php function for category tree
    public function categorytree($parent = 0,  $user_tree_array = ''){
        if(!is_array($user_tree_array))
            $user_tree_array = array();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM category WHERE status = 'True' AND parentid =".$parent;
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid', $parent);
        $statement->execute();
        $result = $statement->fetchAll();

        if(count($result) > 0){

            foreach ($result as $row){

                $user_tree_array[] = "<li style=\"width: max-content\"> <a style=\"font-size: 12px; font-family: audiowide-regular-webfont;\" href='/category/".$row['id']."'>".$row['title']."</a>";
                $user_tree_array = $this->categorytree($row['id'], $user_tree_array);
                $user_tree_array[]="&nbsp;";
            }
            $user_tree_array[] = "</li>";
        }
        return $user_tree_array;
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}
