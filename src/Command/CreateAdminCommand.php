<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates new user or makes an existent user admin',
    aliases: ['app:add-admin'],
    hidden: false
)]
class CreateAdminCommand extends Command
{

    public function __construct(private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'email')
            ->addArgument('password', InputArgument::OPTIONAL, 'password', 'Required for new user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $user = $this->userRepository->findByEmail($email);
        $isUserNew = false;

        if (null === $user) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, $input->getArgument('password')));
            $isUserNew = true;
        }

        $user->setRoles(['ROLE_ADMIN']);

        $this->userRepository->save($user, true);

        $isUserNew
            ? $output->writeln(\sprintf('User %s with role admin successfully created!', $user->getId()))
            : $output->writeln(\sprintf('User %s granted role admin!', $user->getId()))
        ;

        return Command::SUCCESS;
    }
}
