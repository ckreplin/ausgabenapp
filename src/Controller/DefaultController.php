<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\UserHelper;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default_index_action")
     */
    public function indexAction()
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            $count  = count(
                $this
                    ->getDoctrine()
                    ->getRepository(User::class)
                    ->findAll()
            );

            if ($count == 0) {
                return $this->redirectToRoute('default_user_new');
            } else {
                return $this->redirectToRoute('app_login');
            }
        } else {
            return $this->redirectToRoute('trusted_index_action');
        }
    }

    /**
     * @Route("/new", name="default_user_new", methods={"GET","POST"})
     */
    public function createUserAction(Request $request, UserPasswordEncoderInterface $encoder, UserHelper $userHelper)
    {
        $count = count(
            $this
                ->getDoctrine()
                ->getRepository(User::class)
                ->findAll()
        );

        if ($count == 0 || $userHelper->isUserCreationAllowed()) {
            $user = new User();
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setRoles(['ROLE_USER']);
                $plainPassword = $form->get('password')->getData();
                $encoded = $encoder->encodePassword($user, $plainPassword);
                $user->setPassword($encoded);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('trusted_index_action');
            }

            return $this->render(
                'trusted/user/new.html.twig',
                [
                    'user' => $user,
                    'form' => $form->createView(),
                ]
            );
        } else {
            throw $this->createAccessDeniedException('You cannot access this page!');
        }
    }
}