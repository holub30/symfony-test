<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/comment/articles/{id}', name:'comment_post')]
    #[IsGranted('ROLE_USER')]
    public function post(Request $request, EntityManagerInterface $em, Article $article): Response
    {
        if (!$article->getIsPublished()) {
            throw $this->createNotFoundException('Article is not published');
        }

        $form = $this->createForm(CommentFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Comment $comment */
            $comment = $form->getData();

            /** @var User $user */
            $user = $this->getUser();

            $comment->setOwner($user);
            $comment->setRelatedArticle($article);

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Comment posted');

            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }

        return $this->render('comment/post.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/comment/delete/{id}', name: 'comment_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, EntityManagerInterface $em, CommentRepository $commentRepository, int $id): Response
    {
        $comment = $commentRepository->find($id);

        $em->remove($comment);
        $em->flush();

        $route = $request->headers->get('referer');

        return $this->redirect($route);
    }
}
