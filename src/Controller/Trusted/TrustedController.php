<?php

namespace App\Controller\Trusted;

use App\Entity\Item;
use App\Entity\Setting;
use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/trusted")
 */
class TrustedController extends AbstractController
{
    private function getRepository()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Item::class)
            ->setUser($this->getUser());
    }

    /**
     * @Route("/", name="trusted_index_action")
     */
    public function indexAction()
    {
        $balance = $this
            ->getRepository()
            ->calculateCurrentBalance();

        $saldo = $balance[0]['income'] - $balance[0]['outgo'];
        $saldoShared = $balance[0]['income_shared'] - $balance[0]['outgo_shared'];
        $allowed = $this->isUserCreationAllowed();

        return $this->render(
            'trusted/index.html.twig',
            [
                'saldo' => $saldo,
                'saldoShared' => $saldoShared,
                'isUserCreationAllowed' => $allowed,
            ]
        );
    }

    /**
     * @Route("/user_creation_switch", name="trusted_user_creation_switch")
     */
    public function userCreationSwitchAction()
    {
        $setting = $this
            ->getDoctrine()
            ->getRepository(Setting::class)
            ->getUserCreationAllowed();
        $entityManager = $this->getDoctrine()->getManager();

        $setting->setVal($setting->getVal() == 1 ? 0 : 1);
        $entityManager->persist($setting);
        $entityManager->flush();

        return $this->redirectToRoute('trusted_index_action');
    }

    private function isUserCreationAllowed()
    {
        $setting = $this
            ->getDoctrine()
            ->getRepository(Setting::class)
            ->getUserCreationAllowed();
        $entityManager = $this->getDoctrine()->getManager();

        if (is_null($setting)) {
            $setting = new Setting();
            $setting->setCode('user_creation');
            $setting->setVal(1);
            $entityManager->persist($setting);
            $entityManager->flush();
            $allowed = true;
        } else {
            $allowed = $setting->getVal() == 1;
        }

        return $allowed;
    }
}