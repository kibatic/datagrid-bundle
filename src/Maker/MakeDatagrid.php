<?php

namespace Kibatic\DatagridBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Kibatic\DatagridBundle\Grid\GridBuilder;
use Kibatic\DatagridBundle\Grid\Template;
use Kibatic\UX\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityDetails;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Validation;
use function Symfony\Component\String\u;

final class MakeDatagrid extends AbstractMaker
{
    public function __construct(
        private DoctrineHelper $entityHelper,
        private Generator $generator,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:datagrid';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new datagrid class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::REQUIRED, \sprintf('The name of the datagrid class (e.g. <fg=yellow>%sGridBuilder</>)', Str::asClassName(Str::getRandomTerm())))
            ->addArgument('entity', InputArgument::REQUIRED, 'The name of Entity or fully qualified model class name that the new datagrid will be listing')
        ;

        $inputConfig->setArgumentAsNonInteractive('bound-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity')) {
            $argument = $command->getDefinition()->getArgument('entity');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(fn ($answer) => Validator::existsOrNull($answer, $entities));
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('bound-class', $io->askQuestion($question));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('entity'),
            'Entity\\'
        );

        $entityDetails = $this->entityHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryClassDetails = $generator->createClassNameDetails(
            '\\'.$entityDetails->getRepositoryClass(),
            'Repository\\',
            'Repository'
        );

        $classData = ClassData::create(
            class: \sprintf('Datagrid\%s', $input->getArgument('name')),
            suffix: 'GridBuilder',
            useStatements: [
                $entityClassDetails->getFullName(),
                $repositoryClassDetails->getFullName(),
                GridBuilder::class,
                RouterInterface::class,
                Request::class,
                FormInterface::class,
                Template::class,
                TranslatableMessage::class,
            ],
        );

        $columns = [];

        foreach ($entityDetails->getDisplayFields() as $field) {
            $columns[] = [
                'name' => Str::asHumanWords($field['fieldName']),
                'value' => $field['fieldName'],
                'template' => $this->getColumnTemplateByType($field['type']),
            ];
        }

        $this->generator->generateClass(
            $classData->getFullClassName(),
            \sprintf('%s/../templates/maker/GridBuilder.tpl.php', \dirname(__DIR__)),
            [
                'class_data' => $classData,
                'repository_class' => $repositoryClassDetails->getShortName(),
                'entity_short_name' => $entityClassDetails->getShortName(),
                'entity_var' => lcfirst($entityClassDetails->getShortName()),
                'entity_snake_case' => Str::asSnakeCase($entityClassDetails->getShortName()),
                'query_entity_alias' => strtolower($entityClassDetails->getShortName()[0]),
                'columns' => $columns,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    private function getColumnTemplateByType(string $type): ?string
    {
        return match ($type) {
            'datetime' => 'Template::DATETIME',
            'datetime_immutable' => 'Template::DATETIME',
            'boolean' => 'Template::BOOLEAN',
            default => null,
        };
    }

    private function decamel(string $string): string
    {
        return ucfirst(strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $string)));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
//        $dependencies->addClassDependency(AbstractType::class, 'form');
//        $dependencies->addClassDependency(DoctrineBundle::class, 'orm', false);
    }
}
