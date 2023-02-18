<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{

    #[Route('/register', name: 'app_register')]
    #[IsGranted('IS_ANONYMOUS')]
    public function register(UserRepository $userRepository,UserPasswordHasherInterface $passwordHasher, Request $request): Response
    {
        $form = $this->createForm(RegisterFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

            $userRepository->save($user, true);

            $this->addFlash('success', 'Account created!');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig',[
            'form' => $form
        ]);
    }

    #[Route('/login', name: 'app_login')]
    #[IsGranted('PUBLIC_ACCESS')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/reset', name: 'app_reset_pwd')]
    #[IsGranted('IS_AUTHENTICATED')]
    public function resetPassword(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, Request $request): Response
    {
        $form = $this->createForm(ResetPasswordFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            $isPasswordValid = $passwordHasher->isPasswordValid($user, $form['oldPassword']->getData());
            if (!$isPasswordValid) {
                $this->addFlash('warning', 'Wrong password');

                return $this->redirectToRoute('app_reset_pwd');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $form['newPassword']->getData()));

            $userRepository->save($user, true);

            $this->addFlash('success', 'Password changed');

            return $this->redirectToRoute('app_homepage');
        }

        return $this->render('security/reset.html.twig', [
            'form' => $form,
        ]);
    }
}
