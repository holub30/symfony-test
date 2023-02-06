<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ArticleCreatedByAssignmentListener
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof Article) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $entity->setCreatedBy($user);
        }
    }
}
