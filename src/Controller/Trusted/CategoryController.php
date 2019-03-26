<?php

namespace App\Controller\Trusted;

use App\Entity\Category;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/trusted/category")
 */
class CategoryController extends AbstractController
{
    /**
     * @Route("/", name="category_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        return $this->render(
            'trusted/category/index.html.twig',
            [
                'categories' => $this->getRepository()->list(),
            ]
        );
    }

    /**
     * @Route("/new", name="category_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setUser($this->getUser());

            if ($category->getShared()) {
                $category->setLuxury(false);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute(
                'category_index',
                [
                    'request' => $request,
                ]
            );
        }

        return $this->render(
            'trusted/category/new.html.twig',
            [
                'category' => $category,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="category_show", methods={"GET"})
     */
    public function show(Request $request, Category $category): Response
    {
        if ($this->isAccessDenied($category)) {
            throw new AccessDeniedHttpException();
        }

        return $this->render(
            'trusted/category/show.html.twig',
            [
                'category' => $category,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="category_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Category $category): Response
    {
        if ($this->isAccessDenied($category)) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'category_index',
                [
                    'id' => $category->getid(),
                ]
            );
        }

        return $this->render(
            'trusted/category/edit.html.twig',
            [
                'category' => $category,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="category_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isAccessDenied($category)) {
            throw new AccessDeniedHttpException();
        }

        if ($this->isCsrfTokenValid('delete'.$category->getid(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('category_index');
    }

    private function getRepository()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Category::class)
            ->setUser($this->getUser());
    }

    /**
     * @param $category Category
     * @return bool
     */
    private function isAccessDenied($category)
    {
        return !$category->getShared() && !$this->getRepository()->findOneById($category->getId());
    }
}
