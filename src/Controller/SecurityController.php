<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ResetPasswordFormType;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\PasswordResetToken;
use App\Form\ForgotPasswordFormType;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PasswordResetTokenRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {            
            $this->addFlash('danger', $error->getMessage());
        }
        $showResendLink = false;
        
        if ($error instanceof CustomUserMessageAccountStatusException && $error->getMessageKey() === 'account_not_verified') {
            $showResendLink = true;
            $error = null;
            }
            
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'showResendLink' => $showResendLink,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
    ): Response {
        $form = $this->createForm(ForgotPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim($form->get('email')->getData()));

            $user = $userRepository->findOneBy([
                'email' => $email,
            ]);

            if ($user && $user->getEmail()) {
                $token = bin2hex(random_bytes(32));

                $resetToken = new PasswordResetToken();
                $resetToken->setUser($user);
                $resetToken->setToken(hash('sha256', $token));
                $resetToken->setExpiresAt(
                    new \DateTimeImmutable('+1 hour')
                );

                $entityManager->persist($resetToken);
                $entityManager->flush();

                $url = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
                );

                $emailService->sendPasswordReset($user, $url);
            }

            $this->addFlash(
                'success',
                'Si un compte correspond à cette adresse, '
                . 'un email contenant un lien de réinitialisation vient d’être envoyé.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form,
        ]);

    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        PasswordResetTokenRepository $passwordResetTokenRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $resetToken = $passwordResetTokenRepository->findOneBy([
        'token' => hash('sha256', $token),
        ]);
        
        if (
            !$resetToken
            || $resetToken->getExpiresAt() < new \DateTimeImmutable()
        ) {
            throw $this->createNotFoundException(
                'Ce lien de réinitialisation est invalide ou a expiré.'
            );
        }

        $user = $resetToken->getUser();

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $password)
            );

            $entityManager->remove($resetToken);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
        ]);
    }
}
