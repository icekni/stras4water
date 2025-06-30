<?php

namespace App\Controller;

use App\Repository\LienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LienController extends AbstractController
{
    #[Route('/lien/{token}', name: 'app_lien_redirect')]
    public function track(
        string $token,
        LienRepository $lienRepo,
        EntityManagerInterface $em
    ): Response {
        $lien = $lienRepo->findOneBy(['token' => $token]);
        if (!$lien) {
            throw $this->createNotFoundException('Lien introuvable');
        }

        $lien->ajouterClic();
        $em->flush();

        return $this->redirect($lien->getUrlCible());
    }

}