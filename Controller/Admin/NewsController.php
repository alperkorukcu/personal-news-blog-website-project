<?php

namespace App\Controller\Admin;

use App\Entity\Admin\News;
use App\Form\Admin\NewsType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/news")
 */
class NewsController extends AbstractController
{
    /**
     * @Route("/", name="admin_news_index", methods="GET")
     */
    public function index(NewsRepository $newsRepository): Response
    {
        return $this->render('admin/news/index.html.twig', ['news' => $newsRepository->findAll()]);
    }

    /**
     * @Route("/new", name="admin_news_new", methods="GET|POST")
     */
    public function new(Request $request, CategoryRepository $categoryrepository): Response
    {
        $catlist = $categoryrepository->findAll();
        $catname = $categoryrepository->findBy(['id' => 0]);

        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();

            return $this->redirectToRoute('admin_news_index');
        }

        return $this->render('admin/news/new.html.twig', [
            'news' => $news,
            'catlist' => $catlist,
            'catname' => $catname,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_news_show", methods="GET")
     */
    public function show(News $news): Response
    {
        return $this->render('admin/news/show.html.twig', ['news' => $news]);
    }

    /**
     * @Route("/{id}/edit", name="admin_news_edit", methods="GET|POST")
     */
    public function edit(Request $request, News $news, CategoryRepository $categoryrepository): Response
    {
        $catlist = $categoryrepository->findAll();
        $catname = $categoryrepository->findBy(['id' => $news->getCategoryId()]);

        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Successfully Saved!');
            return $this->redirectToRoute('admin_news_edit', ['id' => $news->getId()]);
        }

        return $this->render('admin/news/edit.html.twig', [
            'news' => $news,
            'catlist' => $catlist,
            'catname' => $catname,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/iedit", name="admin_news_iedit", methods="GET|POST")
     */
    public function iedit(Request $request, $id, News $news): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_news_edit', ['id' => $news->getId()]);
        }

        return $this->render('admin/news/image_edit.html.twig', [
            'news' => $news,
            'id' => $id,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/iupdate", name="admin_news_iupdate", methods="POST")
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

        return $this->redirectToRoute('admin_news_iedit', ['id' => $news->getId()]);

    }

    /**
     * @Route("/{id}", name="admin_news_delete", methods="DELETE")
     */
    public function delete(Request $request, News $news): Response
    {
        if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($news);
            $em->flush();
        }

        return $this->redirectToRoute('admin_news_index');
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