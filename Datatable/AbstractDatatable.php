<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Datatable;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\EntityManagerInterface;
use Sg\DatatablesBundle\Datatable\AbstractDatatable as SgDatatable;
use Sg\DatatablesBundle\Datatable\Column\ActionColumn;
use Doctrine\ORM\QueryBuilder;
use Sg\DatatablesBundle\Datatable\Style;
use Sg\DatatablesBundle\Response\DatatableResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractDatatable extends SgDatatable
{
    protected const USE_VOTER_CHECK = false;

    protected const DELETE_ACTION = true;
    protected const EDIT_ACTION = true;
    protected const SHOW_ACTION = true;

    protected const TABLE_CLASSES = 'table table-condensed table-responsive-sm';

    protected const DELETE_ACTION_STYLE = 'btn btn-danger';
    protected const EDIT_ACTION_STYLE = 'btn btn-warning';
    protected const SHOW_ACTION_STYLE = 'btn btn-secondary';

    protected const DELETE_ACTION_ICON = 'fa fa-trash';
    protected const EDIT_ACTION_ICON = 'fa fa-pencil';
    protected const SHOW_ACTION_ICON = 'fa fa-search-plus';

    protected const DELETE_ACTION_TITLE = 'Delete'; //TODO: find existing translations
    protected const EDIT_ACTION_TITLE = 'sg.datatables.actions.edit';
    protected const SHOW_ACTION_TITLE = 'sg.datatables.actions.show';

    protected const ID_NAME = 'id';
    protected const ROUTE_ID_PARAMETER_NAME = self::ID_NAME;

    /** @var DatatableResponse */
    protected $responseService;

    protected $isBuilt = false;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $securityToken,
        TranslatorInterface $translator,
        RouterInterface $router,
        EntityManagerInterface $em,
        \Twig_Environment $twig,
        DatatableResponse $responseService
    ) {
        parent::__construct($authorizationChecker, $securityToken, $translator, $router, $em, $twig);
        $this->responseService = $responseService;
    }

    public function getResponse(?array $options = []): JsonResponse
    {
        if (!$this->isBuilt) {
            $this->buildDatatable($options);
        }
        $this->responseService->setDatatable($this);

        $datatableQueryBuilder = $this->responseService->getDatatableQueryBuilder();

        $qb = $datatableQueryBuilder->getQb();
        $this->modifyQueryBuilder($qb);

        return $this->responseService->getResponse();
    }

    public function modifyQueryBuilder(QueryBuilder $qb)
    {
        //Can be overridden to modify queryBuilder
    }

    /**
     * {@inheritdoc}
     */
    public function buildDatatable(?array $options = [])
    {
        $this->language->set([]);

        $this->ajax->set($this->getAjaxOptions($options));

        $this->options->set(array_merge(
            [
                'classes' => static::TABLE_CLASSES,
            ],
            $options
        ));

        $this->features->set([]);

        $this->addColumns($options);

        $this->addActions($options);

        $this->isBuilt = true;
        return $this;
    }

    abstract protected function addColumns(array $options = []): void;

    protected function getAjaxOptions($options = []): array
    {
        return [
            'type' => 'POST',
            'url' => $this->getAjaxUrl($options),
        ];
    }

    protected function getAjaxUrl($options = []): string
    {
        return $this->router->generate($this->getRoute('result'));
    }

    protected function addActions(array $options = []): void
    {
        $actions = $this->getActions();
        $this->columnBuilder
            ->add(null, ActionColumn::class, [
                'title' => $this->translator->trans('sg.datatables.actions.title'),
                'actions' => $actions,
                'class_name' => 'action-column',
                'width' => '180px',
            ])
        ;
    }

    protected function getActions(): array
    {
        $actions = [];
        if (static::DELETE_ACTION) {
            $actions['delete'] = $this->getDeleteAction();
        }
        if (static::EDIT_ACTION) {
            $actions['edit'] = $this->getEditAction();
        }
        if (static::SHOW_ACTION) {
            $actions['show'] = $this->getShowAction();
        }
        return $actions;
    }

    protected function getDeleteAction()
    {
        return [
            'route' => $this->getRoute('delete'),
            'route_parameters' => [static::ROUTE_ID_PARAMETER_NAME => static::ID_NAME],
            'label' => null,
            'icon' => static::DELETE_ACTION_ICON,
            'attributes' => [
                'rel' => 'tooltip',
                'title' => $this->translator->trans(static::DELETE_ACTION_TITLE),
                'class' => static::DELETE_ACTION_STYLE,
                'role' => 'button'
            ],
            'render_if' => function ($row) {
                return $this->checkIsGranted('DELETE', $row[static::ID_NAME]);
            },
        ];
    }

    protected function getEditAction()
    {
        return [
            'route' => $this->getRoute('edit'),
            'route_parameters' => [static::ROUTE_ID_PARAMETER_NAME => static::ID_NAME],
            'label' => null,
            'icon' => static::EDIT_ACTION_ICON,
            'attributes' => [
                'rel' => 'tooltip',
                'title' => $this->translator->trans(static::EDIT_ACTION_TITLE),
                'class' => static::EDIT_ACTION_STYLE,
                'role' => 'button'
            ],
            'render_if' => function ($row) {
                return $this->checkIsGranted('EDIT', $row[static::ID_NAME]);
            },
        ];
    }

    protected function getShowAction()
    {
        return [
            'route' => $this->getRoute('show'),
            'route_parameters' => [static::ROUTE_ID_PARAMETER_NAME => static::ID_NAME],
            'label' => null,
            'icon' => static::SHOW_ACTION_ICON,
            'attributes' => [
                'rel' => 'tooltip',
                'title' => $this->translator->trans(static::SHOW_ACTION_TITLE),
                'class' => static::SHOW_ACTION_STYLE,
                'role' => 'button'
            ],
            'render_if' => function ($row) {
                return $this->checkIsGranted('VIEW', $row[static::ID_NAME]);
            },
        ];
    }

    protected function checkIsGranted($voterSuffix, $entryId): bool
    {
        return static::USE_VOTER_CHECK === false || $this->authorizationChecker->isGranted(
            strtoupper($this->getRoute($voterSuffix)),
            $this->getEntityManager()->getReference($this->getEntity(), $entryId)
        );
    }

    protected function getRoute($suffix): string
    {
        return str_replace('datatable', '', $this->getName()).$suffix;
    }

    protected function getEntityName(): string
    {
        return (new \ReflectionClass($this->getEntity()))->getShortName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return Inflector::tableize($this->getEntityName()).'_datatable';
    }
}
