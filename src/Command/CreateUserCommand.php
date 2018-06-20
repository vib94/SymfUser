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

class CreateUserCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
    
    protected function configure()
    {
        $this->setName('app:create-user')
        ->setDescription('Creates a new user.')
        ->setHelp('This command allows you to create a user...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);

        $section1 = $output->section();
        $section2 = $output->section();
        $helper = $this->getHelper('question');
        $userNameExists = true;

        while($userNameExists)
        {
            $question = new Question('Please enter the username : ');
            $userName = $helper->ask($input, $output, $question);
            $user = $this->entityManager->getRepository(User::class)->findOneBy(array('username' => $userName));
            if($user)
            {
                $section1->writeln('username '.$userName.' already exits');
            } else
            {
                $userNameExists = false;
            }
        }

        $question = new Question('Please enter the user password : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $userPassword = $helper->ask($input, $output, $question);

        $roles = $this->entityManager->getRepository(Role::class)->findAll();
        $choices = array();
        foreach($roles as $role)
        {
            $choices[] = $role->getName();
        }

        $question = new ChoiceQuestion(
            'Please choose the user roles',
            $choices,
            '0'
        );
        $question->setMultiselect(true);
        $userRoles = $helper->ask($input, $output, $question);
        //$question->setMaxAttempts(2);

        
        $user = new User();
        $user->setUsername($userName);
        $user->setPlainPassword($userPassword);
        $user->setRoles($userRoles);

        $this->entityManager->persist($user);         
        $this->entityManager->flush($user);  

        $section1->writeln('<fg=green>User '.$userName.' created ! [OK]</>');
        // overwrite() replaces all the existing section contents with the given content
       // $section1->overwrite('Goodbye');
        // Output now displays "Goodbye\nWorld!\n"

        // clear() deletes all the section contents...
        //$section2->clear();

    }
}