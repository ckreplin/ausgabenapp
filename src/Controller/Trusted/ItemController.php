<?php

namespace App\Controller\Trusted;

use App\Entity\Item;
use App\Form\ItemType;
use App\Service\DateHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/trusted/item")
 */
class ItemController extends AbstractController
{
    /**
     * @Route("/{direction}", name="item_index", methods={"GET"})
     */
    public function index($direction, Request $request, DateHelper $dateHelper): Response
    {
        $isShared = $request->get('shared') == 1;

        $items = $this
            ->getRepository()
            ->list($direction, $isShared);

        $itemProcessed = [];
        $lastMonth = '';

        /** @var Item $item */
        foreach ($items as $key => $item) {
            $temp = [];
            $temp['id'] = $item->getId();
            $temp['dateAt'] = $item->getDateAt();
            $temp['category'] = $item->getCategory();
            $temp['title'] = $item->getTitle();
            $temp['amount'] = $item->getAmount();
            $temp['shared'] = $item->getShared();
            $temp['user'] = $item->getUser();

            $temp['month'] = $dateHelper->getMonthName((int)$item->getDateAt()->format('m'));
            $temp['year'] = (int)$item->getDateAt()->format('Y');

            $temp['printMonth'] = false;
            if ($key === 0) {
                $temp['printMonth'] = true;
                $lastMonth = $temp['month'];
            } else {
                if ($lastMonth === $temp['month']) {
                    $temp['printMonth'] = false;
                } else {
                    $temp['printMonth'] = true;
                }
                $lastMonth = $temp['month'];
            }

            array_push($itemProcessed, $temp);
        }

        return $this->render(
            'trusted/item/index.html.twig',
            [
                'items' => $itemProcessed,
                'direction' => $direction,
                'shared' => $isShared,
                'all' => false,
            ]
        );
    }

    /**
     * @Route("/{direction}/all", name="item_index_all", methods={"GET"})
     */
    public function indexAll($direction, Request $request, DateHelper $dateHelper): Response
    {
        $isShared = $request->get('shared') == 1;

        $items = $this
            ->getRepository()
            ->listAll($direction, $isShared);

        $itemProcessed = [];
        $lastMonth = '';

        /** @var Item $item */
        foreach ($items as $key => $item) {
            $temp = [];
            $temp['id'] = $item->getId();
            $temp['dateAt'] = $item->getDateAt();
            $temp['category'] = $item->getCategory();
            $temp['title'] = $item->getTitle();
            $temp['amount'] = $item->getAmount();
            $temp['shared'] = $item->getShared();
            $temp['user'] = $item->getUser();

            $temp['month'] = $dateHelper->getMonthName((int)$item->getDateAt()->format('m'));
            $temp['year'] = (int)$item->getDateAt()->format('Y');

            $temp['printMonth'] = false;
            if ($key === 0) {
                $temp['printMonth'] = true;
                $lastMonth = $temp['month'];
            } else {
                if ($lastMonth === $temp['month']) {
                    $temp['printMonth'] = false;
                } else {
                    $temp['printMonth'] = true;
                }
                $lastMonth = $temp['month'];
            }

            array_push($itemProcessed, $temp);
        }

        return $this->render(
            'trusted/item/index.html.twig',
            [
                'items' => $itemProcessed,
                'direction' => $direction,
                'shared' => $isShared,
                'all' => true,
            ]
        );
    }

    /**
     * @Route("/new/{direction}", name="item_new", methods={"GET","POST"})
     */
    public function new(Request $request, $direction): Response
    {
        $isShared = $request->get('shared') == 1;

        $item = new Item();
        $form = $this->createForm(
            ItemType::class,
            $item,
            [
                'direction' => $direction,
                'shared' => $isShared,
                'user' => $this->getUser()->getId(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item->setIncome($direction === 'income');
            $item->setUser($this->getUser());
            $item->setShared($isShared);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();

            $route = $request->get('all') ? 'item_index_all' : 'item_index';
            return $this->redirectToRoute(
                $route,
                [
                    'direction' => $direction,
                    'shared' => $isShared,
                ]
            );
        }

        return $this->render(
            'trusted/item/new.html.twig',
            [
                'item' => $item,
                'form' => $form->createView(),
                'direction' => $direction,
                'all' => $request->get('all')
            ]
        );
    }

    /**
     * @Route("/show/{direction}/{id}", name="item_show", methods={"GET"})
     */
    public function show(Request $request, Item $item, $direction): Response
    {
        if ($this->isAccessDenied($item)) {
            throw new AccessDeniedHttpException();
        }

        $isShared = $request->get('shared') == 1;

        return $this->render(
            'trusted/item/show.html.twig',
            [
                'item' => $item,
                'direction' => $direction,
                'shared' => $isShared,
                'all' => $request->get('all'),
            ]
        );
    }

    /**
     * @Route("/edit/{direction}/{id}/edit", name="item_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Item $item, $direction): Response
    {
        if ($this->isAccessDenied($item)) {
            throw new AccessDeniedHttpException();
        }

        $isShared = $request->get('shared') == 1;

        $form = $this->createForm(
            ItemType::class,
            $item,
            [
                'direction' => $direction,
                'user' => $this->getUser()->getId(),
                'shared' => $isShared,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($item->getShared()) {
                $item->setUser($this->getUser());
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                $request->get('all') == 1 ? 'item_index_all' : 'item_index',
                [
                    'id' => $item->getId(),
                    'direction' => $direction,
                    'shared' => $isShared,
                ]
            );
        }

        return $this->render(
            'trusted/item/edit.html.twig',
            [
                'item' => $item,
                'form' => $form->createView(),
                'direction' => $direction,
                'all' => $request->get('all')
            ]
        );
    }

    /**
     * @Route("/delete/{direction}/{id}", name="item_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Item $item, $direction): Response
    {
        if ($this->isAccessDenied($item)) {
            throw new AccessDeniedHttpException();
        }

        $isShared = false;

        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $isShared = $item->getShared();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($item);
            $entityManager->flush();
        }

        $route = $request->get('all') ? 'item_index_all' : 'item_index';

        return $this->redirectToRoute(
            $route,
            [
                'direction' => $direction,
                'shared' => $isShared,
            ]
        );
    }

    private function getRepository()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Item::class)
            ->setUser($this->getUser());
    }

    /**
     * @param $request Request
     * @param $item Item
     * @return bool
     */
    private function isAccessDenied($item)
    {
        return !$item->getShared() && !$this->getRepository()->findOneById($item->getId());
    }
}
