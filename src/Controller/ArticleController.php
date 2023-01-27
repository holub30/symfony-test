<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ArticleController extends AbstractController
{
    #[Route('/articles', name:'articles_list', methods: [Request::METHOD_GET])]
    public function list(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        return $this->render('article/list.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/articles', 'article_create', methods: [Request::METHOD_POST])]
    public function create(EntityManagerInterface $em, Request $request, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ArticleFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Article $article */
            $article = $form->getData();

            /** @var  UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();

            $newFilename = $fileUploader->uploadArticleImage($uploadedFile);
            $article->setTitleImage($newFilename);

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article created');

            return $this->redirectToRoute('list_articles');
        }

        return $this->render('article/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/articles/{id}', methods: [Request::METHOD_GET])]
    public function show(ArticleRepository $articleRepository, string $id): Response
    {
        $article = $articleRepository->find($id);

        if (null === $article) {
            throw $this->createNotFoundException('No article found');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article
        ]);
    }
}
