<?php

namespace App\Controller;

use App\Repository\Admin\SettingsRepository;
use App\Repository\Admin\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, SettingsRepository $settingsRepository, CategoryRepository $categoryRepository, UserRepository $userRepository): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM news WHERE status = 'True' ORDER BY ID DESC LIMIT 4";
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid', $parent);
        $statement->execute();
        $sliders = $statement->fetchAll();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'data' => $data,
            'cats' => $cats,
            'sliders' => $sliders,
        ]);
    }

    /**
     * @Route("/loginerror", name="app_login_error")
     */
    public function loginerror(AuthenticationUtils $authenticationUtils, SettingsRepository $settingsRepository, CategoryRepository $categoryRepository, UserRepository $userRepository): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $data = $settingsRepository->findAll();
        $cats = $this->categorytree();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM news WHERE status = 'True' ORDER BY ID DESC LIMIT 4";
        $statement = $em->getConnection()->prepare($sql);
        //$statement->bindValue('parentid', $parent);
        $statement->execute();
        $sliders = $statement->fetchAll();

        $this->addFlash('noauthentication', 'You do not have the required authentication!');

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'data' => $data,
            'cats' => $cats,
            'sliders' => $sliders,
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

                $user_tree_array[] = "<li> <a style=\"font-size: 12px\" href='/category/".$row['id']."'>".$row['title']."</a>";
                $user_tree_array = $this->categorytree($row['id'], $user_tree_array);
                $user_tree_array[]="&nbsp;";
            }
            $user_tree_array[] = "</li>";
        }
        return $user_tree_array;
    }
}
