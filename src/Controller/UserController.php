<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserController extends AbstractController
{
    #[Route('/', name: 'registroUser')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        //Si ya esta logueado y quiere acceder al registro se le manda directo otra vez a sus tareas
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_tareas');
        }
        $user = new User();

        $registro = $this->createForm(UserType::class, $user);
        $registro->handleRequest($request);
        
        if($registro->isSubmitted() && $registro->isValid()){

            $contrasena = $registro->get('password')->getData();
            $hashContrasena = $passwordHasher->hashPassword(
                $user,
                $contrasena,
            );
            
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($hashContrasena);


            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('login');
        }

        return $this->render('user/index.html.twig', [
            'registro' => $registro->createView(),
        ]);
    }
}
