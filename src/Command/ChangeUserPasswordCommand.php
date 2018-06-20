<?php

namespace Vib\SymfUser\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Vib\SymfUser\Entity\User;
use Vib\SymfUser\Entity\Role;

class ChangeUserPasswordCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
    
    protected function configure()
    {
        $this->setName('app:change-user-password')
        ->setDescription('Change a user password.')
        ->setHelp('This command allows you to change a user password...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Change User password',
            '============',
            '',
        ]);

        $section1 = $output->section();
        $section2 = $output->section();
        $helper = $this->getHelper('question');
        $userNameExists = false;

        while(!$userNameExists)
        {
            $question = new Question('Please enter the username to change password : ');
            $userName = $helper->ask($input, $output, $question);
            $user = $this->entityManager->getRepository(User::class)->findOneBy(array('username' => $userName));
            if($user)
            {
                $userNameExists = true;
            } else
            {
                $section1->writeln('username '.$userName.' doesnt exist');
            }
        }

        $question = new Question('Please enter the user password : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $userPassword = $helper->ask($input, $output, $question);

        $user->setPlainPassword($userPassword);

        $this->entityManager->persist($user);         
        $this->entityManager->flush($user);  

        $section1->writeln('<fg=green>User '.$userName.' password changed ! [OK]</>');
        // overwrite() replaces all the existing section contents with the given content
       // $section1->overwrite('Goodbye');
        // Output now displays "Goodbye\nWorld!\n"

        // clear() deletes all the section contents...
        //$section2->clear();

    }
}