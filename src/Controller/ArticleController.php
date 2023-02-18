<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\FileUploader;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ArticleController extends AbstractController
{
    #[Route('/articles', name:'articles_list', methods: [Request::METHOD_GET])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function list(ArticleRepository $articleRepository, Request $request, FileUploader $fileUploader): Response
    {
        $queryBuilder = $articleRepository->createOrderByCreatedAtQueryBuilder();
        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int)$request->query->get('page', 1),
            5
        );

        $form = $this->create($articleRepository, $request, $fileUploader)->getContent();

        return $this->render('article/list.html.twig', [
            'pager' => $pagerfanta,
            'form' => $form
        ]);
    }

    #[Route('/articles/{id}', name: 'article_show', methods: [Request::METHOD_GET])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function show(FileUploader $fileUploader, ArticleRepository $articleRepository, CommentRepository $commentRepository, Request $request, string $id): Response
    {
        $article = $articleRepository->find($id);

        if (null === $article) {
            throw $this->createNotFoundException('Article not found');
        }

        $queryBuilder = $commentRepository->createOrderByPostedAtQueryBuilder($article);
        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int)$request->query->get('page', 1),
            2
        );

        $form = $this->edit($article, $articleRepository, $request, $fileUploader)->getContent();

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'form' => $form,
            'pager' => $pagerfanta,
        ]);
    }

    #[Route('/articles', name: 'article_create', methods: [Request::METHOD_POST])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(ArticleRepository $articleRepository, Request $request, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ArticleFormType::class, null, [
            'action' => $this->generateUrl('article_create'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Article $article */
            $article = $form->getData();

            /** @var  UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();

            $newFilename = $fileUploader->uploadArticleImage($uploadedFile);
            $article->setTitleImage($newFilename);

            if ($form->get('submitBtn')->isClicked()) {
                $article->setIsPublished(true);
            }

            $articleRepository->save($article, true);

            $this->addFlash('success', 'Article created');

            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/create.html.twig', ['form' => $form]);
    }

    #[Route('/articles/{id}/post', name: 'article_post', methods: [Request::METHOD_GET])]
    #[IsGranted('ROLE_ADMIN')]
    public function post(ArticleRepository $articleRepository, Article $article): Response
    {
        if ($article->getIsPublished()) {
            throw $this->createNotFoundException('Article already published');
        }

        $article->setIsPublished(true);
        $article->setCreatedAt(new \DateTimeImmutable());

        $articleRepository->save($article, true);

        $this->addFlash('success', 'Article successfully posted!');

        return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
    }


    #[Route('/articles/{id}', name:'article_edit', methods: [Request::METHOD_PATCH])]
    #[IsGranted('EDIT', 'article')]
    public function edit(Article $article, ArticleRepository $articleRepository, Request $request, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ArticleFormType::class, $article, [
            'action' => $this->generateUrl('article_edit', ['id' => $article->getId()]),
            'method' => 'PATCH',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var  UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();

            if (null !== $uploadedFile) {
                $newFilename = $fileUploader->uploadArticleImage($uploadedFile);
                $article->setTitleImage($newFilename);
            }

            $article->setUpdatedAt(new \DateTimeImmutable());
            $article->setUpdatedBy($this->getUser());

            $articleRepository->save($article, true);

            $this->addFlash('success', 'Article updated!');

            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/edit.html.twig', ['form' => $form]);
    }
}
