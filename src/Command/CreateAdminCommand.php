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
    description: 'Creates an admin user.',
    aliases: ['app:add-admin'],
    hidden: false
)]
class CreateAdminCommand extends Command
{

    public function __construct(private UserRepository $objectManager, private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'email')
            ->addArgument('password', InputArgument::REQUIRED, 'password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = new User();
        $admin->setEmail($input->getArgument('email'));
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $input->getArgument('password')));
        $admin->setRoles(['ROLE_ADMIN']);

        $this->objectManager->save($admin, true);

        $output->writeln('Admin user successfully created!');

        return Command::SUCCESS;
    }
}
