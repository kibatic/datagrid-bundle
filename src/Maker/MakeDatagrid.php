<?php

namespace Kibatic\DatagridBundle\Maker;

use Doctrine\ORM\QueryBuilder;
use Kibatic\DatagridBundle\Grid\GridBuilder;
use Kibatic\DatagridBundle\Grid\Template;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityDetails;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;
// TODO: ajouter une colonne avec le template entity (sur l'identifier ?)
final class MakeDatagrid extends AbstractMaker
{
    private string $gridBuilderClassName;

    public function __construct(
        private DoctrineHelper $entityHelper,
        private FormTypeRenderer $formTypeRenderer,
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
            ->addArgument('entity-class', InputArgument::REQUIRED, 'The name of Entity or fully qualified model class name that the new datagrid will be listing')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(fn ($answer) => Validator::existsOrNull($answer, $entities));
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('entity-class', $io->askQuestion($question));
        }

        $defaultGridBuilderClass = Str::asClassName(\sprintf('%s GridBuilder', $input->getArgument('entity-class')));

        $this->gridBuilderClassName = $io->ask(
            \sprintf('Choose a name for your class (e.g. <fg=yellow>%s</>)', $defaultGridBuilderClass),
            $defaultGridBuilderClass
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('entity-class'),
            'Entity\\'
        );

        $entityDetails = $this->entityHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryClassDetails = $generator->createClassNameDetails(
            '\\'.$entityDetails->getRepositoryClass(),
            'Repository\\',
            'Repository'
        );

        $classData = ClassData::create(
            class: \sprintf('Datagrid\%s', $this->gridBuilderClassName),
            extendsClass: GridBuilder::class,
            useStatements: [
                $entityClassDetails->getFullName(),
                $repositoryClassDetails->getFullName(),
                GridBuilder::class,
                PaginatorInterface::class,
                ParameterBagInterface::class,
                QueryBuilder::class,
                RouterInterface::class,
                Request::class,
                RequestStack::class,
                FormInterface::class,
                Template::class,
            ],
        );

        $columns = [];

        foreach ($entityDetails->getDisplayFields() as $field) {
            $columns[] = [
                'name' => ucfirst(strtolower(Str::asHumanWords($field['fieldName']))),
                'value' => $field['fieldName'],
                'template' => $this->getColumnTemplateByType($field['type']),
            ];
        }

        $generator->generateClass(
            $classData->getFullClassName(),
            \sprintf('%s/../templates/maker/GridBuilder.tpl.php', \dirname(__DIR__)),
            [
                'class_data' => $classData,
                'repository_class' => $repositoryClassDetails->getShortName(),
                'entity_short_name' => $entityClassDetails->getShortName(),
                'entity_var' => lcfirst($entityClassDetails->getShortName()),
                'entity_snake_case' => Str::asSnakeCase($entityClassDetails->getShortName()),
                'query_entity_alias' => strtolower($entityClassDetails->getShortName()[0]),
                'entity_display_fields' => $entityDetails->getDisplayFields(),
                'columns' => $columns,
            ]
        );

//        $this->formTypeRenderer->render(
//            $generator->createClassNameDetails(
//                "{$entityClassDetails->getRelativeNameWithoutSuffix()}FiltersType",
//                'Form\\DatagridFilters\\',
//                'Type'
//            ),
//            ['search' => null],
//        );

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
    }
}
