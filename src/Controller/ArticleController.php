<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\ArticleFormType;
use App\Form\CommentFormType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function PHPUnit\Framework\throwException;

final class ArticleController extends AbstractController
{
    #[Route('/articles', name:'articles_list', methods: [Request::METHOD_GET])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function list(ArticleRepository $articleRepository, Request $request): Response
    {
        $queryBuilder = $articleRepository->createOrderByCreatedAtQueryBuilder();
        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int)$request->query->get('page', 1),
            5
        );

        return $this->render('article/list.html.twig', [
            'pager' => $pagerfanta,
        ]);
    }

    #[Route('/articles/show/{id}', name: 'article_show', methods: [Request::METHOD_GET])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function show(ArticleRepository $articleRepository, CommentRepository $commentRepository, Request $request, string $id): Response
    {
        $article = $articleRepository->find($id);

        if (null === $article) {
            throw $this->createNotFoundException('Article not found');
        }

        if ($this->isGranted('IS_ANONYMOUS')) {
            $article->setContent(substr($article->getContent(), 0, 50));
        }

        $queryBuilder = $commentRepository->createOrderByPostedAtQueryBuilder($article);
        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int)$request->query->get('page', 1),
            2
        );

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'pager' => $pagerfanta,
        ]);
    }

    #[Route('/articles/create', name: 'article_create')]
    #[IsGranted('ROLE_ADMIN')]
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
            $article->setCreatedBy($this->getUser()->getUserIdentifier());

            if ($form->get('submitBtn')->isClicked()) {
                $article->setIsPublished(true);
            }

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article created');

            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/articles/post/{id}', name: 'article_post', methods: [Request::METHOD_GET])]
    #[IsGranted('ROLE_ADMIN')]
    public function post(EntityManagerInterface $em, Article $article): Response
    {
        if ($article->getIsPublished()) {
            throw $this->createNotFoundException('Article already posted');
        }

        $article->setIsPublished(true);
        $article->setCreatedAt(new \DateTimeImmutable());

        $em->persist($article);
        $em->flush();

        $this->addFlash('success', 'Article successfully posted!');

        return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
    }


    #[Route('/articles/edit/{id}', name:'article_edit')]
    #[IsGranted('EDIT', 'article')]
    public function edit(EntityManagerInterface $em, Request $request, Article $article, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ArticleFormType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var  UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();

            if (null !== $uploadedFile) {
                $newFilename = $fileUploader->uploadArticleImage($uploadedFile);
                $article->setTitleImage($newFilename);
            }

            $article->setUpdatedAt(new \DateTimeImmutable());
            $article->setUpdatedBy($this->getUser()->getUserIdentifier());

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article updated!');

            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form,
            ]);
    }
}
