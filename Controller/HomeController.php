<?php

namespace App\Controller;

use App\Entity\Admin\Comments;
use App\Entity\Admin\Messages;
use App\Entity\User;
use App\Form\Admin\CommentsType;
use App\Form\Admin\MessagesType;
use App\Form\UserType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\NewsRepository;
use App\Repository\Admin\SettingsRepository;
use App\Repository\Admin\ImageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;

class HomeController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
    public function index(SettingsRepository $settingsRepository, CategoryRepository $categoryRepository)
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM news WHERE status = 'True' ORDER BY ID DESC LIMIT 4";
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid', $parent);
        $statement->execute();
        $sliders = $statement->fetchAll();

        return $this->render('home/index.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'sliders' => $sliders,
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about(SettingsRepository $settingsRepository)
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        return $this->render('home/about.html.twig', [
            'data' => $data,
            'cats' => $cats,
        ]);
    }

    /**
     * @Route("/contact", name="contact", methods="GET|POST")
     */
    public function contact(SettingsRepository $settingsRepository, Request $request)
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();
            $this->addFlash('success', 'Your message successfully sent!');

            return $this->redirectToRoute('contact');
        }

        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        return $this->render('home/contact.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'message' => $message,
        ]);
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
     * @Route("/category/{catid}", name="category_news", methods="GET")
     */
    public function CategoryNews($catid, CategoryRepository $categoryRepository)
    {
        $cats = $this->categorytree();
        $data = $categoryRepository->findBy(
            ['id' => $catid]
        );

        $em = $this ->getDoctrine()->getManager();
        $sql = 'SELECT * FROM news WHERE status="True" AND category_id = :catid';
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('catid', $catid);
        $statement->execute();
        $news = $statement->fetchAll();

        return $this->render('home/news.html.twig', [
            'data' => $data,
            'news'  => $news,
            'cats' => $cats,
        ]);
    }

    /**
     * @Route("/newsdetails/{id}", name="news_details", methods="GET")
     */
    public function NewsDetails($id, NewsRepository $newsRepository)
    {
        $cats = $this->categorytree();
        $data = $newsRepository->findBy(
            ['id' => $id]
        );

        return $this->render('home/news.html.twig', [
            'data' => $data,
            'cats' => $cats,
        ]);
    }

    /**
     * @Route("/entry/{id}", name="entry_details", methods="GET|POST")
     */
    public function EntryDetails($id, NewsRepository $newsRepository, ImageRepository $imageRepository, SettingsRepository $settingsRepository, Request $request): Response
    {
        $cats = $this->categorytree();
        $data = $newsRepository->findBy(
            ['id' => $id]
        );

        $images = $imageRepository->findBy(
            ['news_id' => $id]
        );

        $comment = new Comments();
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        if(($this->getUser())){
            $userinfo = $this->getUser();
            $userid = $userinfo->getId();
        }

        $entryid = $id;

        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT created_at FROM news WHERE id = :id';
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('id', $id);
        $statement->execute();
        $timestamp = $statement->fetchAll();

        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT name FROM user, news WHERE news.user_id = user.id';
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('id', $id);
        $statement->execute();
        $authorname = $statement->fetchAll();

        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT u.name, c.comment, c.entry_id, c.status FROM comments c, user u, news n WHERE c.user_id = u.id AND c.entry_id = n.id';
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('id', $id);
        $statement->execute();
        $comment_info = $statement->fetchAll();

        if ($form->isSubmitted()) {
            $comment->setUserId($userid);
            $comment->setEntryId($entryid);
            $comment->setStatus('False');
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Your comment successfully sent!, It\'ll be visible when an Admin approves it.');

            return $this->redirectToRoute('entry_details', ['id' => $id]);
        }


        return $this->render('home/entry_details.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'images' => $images,
            'comment' => $comment,
            'form' => $form->createView(),
            'timestamp' => $timestamp,
            'authorname' => $authorname,
            'comment_info' => $comment_info,
        ]);
    }

    /**
     * @Route("/newuser", name="new_user", methods="GET|POST")
     */
    public function newuser(Request $request, SettingsRepository $settingsRepository, UserRepository $userRepository):Response
    {
        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');

        if($this->isCsrfTokenValid('user-form', $submittedToken)) {
            if($form->isSubmitted()) {
                $emaildata = $userRepository->findBy(
                    ['email' => $user->getEmail()]
                );

                if($emaildata == null){
                    $em = $this->getDoctrine()->getManager();
                    $user->setRoles("ROLE_USER");
                    $user->setStatus("False");
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success', 'Thanks! You have successfully signed up to our site.');

                    return $this->redirectToRoute('app_login');
                }
                else {
                    $this->addFlash('error', $user->getEmail()." there is a user with this email.");
                }
            }
        }

        return $this->render('home/newuser.html.twig' , [
            'form' => $form->createView(),
            'user' => $user,
            'data' => $data,
            'cats' => $cats,
        ]);
    }




}
