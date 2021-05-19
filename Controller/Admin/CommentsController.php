<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Comments;
use App\Form\Admin\CommentsType;
use App\Repository\CommentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/comments")
 */
class CommentsController extends AbstractController
{
    /**
     * @Route("/", name="admin_comments_index", methods="GET")
     */
    public function index(CommentsRepository $commentsRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT u.name, n.title FROM comments c LEFT JOIN user u ON c.user_id = u.id LEFT JOIN news n ON c.entry_id = n.id';
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('id', $id);
        $statement->execute();
        $comment_info = $statement->fetchAll();

        return $this->render('admin/comments/index.html.twig', [
            'comments' => $commentsRepository->findAll(),
            'comment_info' => $comment_info,
        ]);
    }

    /**
     * @Route("/new", name="admin_comments_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $comment = new Comments();
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('admin_comments_index');
        }

        return $this->render('admin/comments/new.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_comments_show", methods="GET")
     */
    public function show(Comments $comment, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT u.name, n.title FROM comments c LEFT JOIN user u ON c.user_id = u.id LEFT JOIN news n ON c.entry_id = n.id';
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('id', $id);
        $statement->execute();
        $comment_info = $statement->fetchAll();

        return $this->render('admin/comments/show.html.twig', [
            'comment' => $comment,
            'comment_info' => $comment_info,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_comments_edit", methods="GET|POST")
     */
    public function edit(Request $request, Comments $comment, $id): Response
    {
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT u.name, n.title, c.entry_id, u.id FROM comments c LEFT JOIN user u ON c.user_id = u.id LEFT JOIN news n ON c.entry_id = n.id';
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('id', $id);
        $statement->execute();
        $comment_info = $statement->fetchAll();


        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('admin_comments_index', ['id' => $comment->getId()]);
        }

        return $this->render('admin/comments/edit.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
            'comment_info' => $comment_info,
        ]);
    }

    /**
     * @Route("/{id}", name="admin_comments_delete", methods="DELETE")
     */
    public function delete(Request $request, Comments $comment): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($comment);
            $em->flush();
        }

        return $this->redirectToRoute('admin_comments_index');
    }
}
