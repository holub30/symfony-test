<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Form\Type\BlogType;
use App\Repository\ArticleRepository;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{

    #[Route('/', name:'list_articles')]
    public function list(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        return $this->render('article/list.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/new')]
    public function new(EntityManagerInterface $em, Request $request, UploadHelper $uploadHelper): Response
    {

        $form = $this->createForm(ArticleFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Article $article */
            $article = $form->getData();

            /** @var  UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();

            $newFilename = $uploadHelper->uploadArticleImage($uploadedFile);
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

    #[Route('/article/{id}')]
    public function show(ArticleRepository $articleRepository, int $id): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found'
            );
        }

        return $this->render('article/show.html.twig', [
            'article' => $article
        ]);
    }
}
