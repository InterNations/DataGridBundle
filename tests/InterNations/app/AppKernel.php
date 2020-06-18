<?php
namespace InterNations\DataGridBundle\Tests\app;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use InterNations\DataGridBundle\Grid\Action\DeleteMassAction;
use InterNations\DataGridBundle\Grid\Action\RowAction;
use InterNations\DataGridBundle\Grid\Column\MassActionColumn;
use InterNations\DataGridBundle\Grid\Column\RangeColumn;
use InterNations\DataGridBundle\Grid\Column\SelectColumn;
use InterNations\DataGridBundle\Grid\Column\TextColumn;
use InterNations\DataGridBundle\Grid\Grid;
use InterNations\DataGridBundle\Grid\Source\Entity;
use InterNations\DataGridBundle\Grid\Source\Source;
use InterNations\DataGridBundle\InterNationsDataGridBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Twig\Markup;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [new FrameworkBundle(), new TwigBundle(), new DoctrineBundle(), new InterNationsDataGridBundle()];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->add('/', 'kernel:gridAction', 'grid');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension(
            'framework',
            ['secret' => 'SECRET', 'form' => true, 'templating' => ['engines' => ['twig']], 'session' => true]
        );
        $c->loadFromExtension(
            'twig',
            ['paths' => [__DIR__]]
        );
        $c->loadFromExtension(
            'doctrine',
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_sqlite',
                            'path' => '%kernel.cache_dir%/db.sqlite',
                        ],
                    ],
                ],
                'orm' => [
                    'mappings' => [
                        'Test' => [
                            'type' => 'annotation',
                            'dir' => __DIR__,
                            'is_bundle' => false,
                            'prefix' => 'InterNations\\DataGridBundle\\Tests\\app',
                        ],
                    ],
                ],
            ]
        );
    }

    public function gridAction(Request $request): Response
    {
        $container = $this->getContainer();

        if ($request->query->get('reset')) {
            $this->createTestData($container);
        }

        $grid = new class ($container, new Entity(TestEntity::class)) extends Grid
        {
            public function setSource(Source $source): void
            {
                $this->addColumn(
                    new RangeColumn(
                        [
                            'id' => 'id',
                            'field' => 'id',
                            'source' => true,
                            'title' => 'ID',
                            'primary' => true,
                        ]
                    )
                );
                $this->addColumn(
                    new TextColumn(
                        [
                            'id' => 'str_value',
                            'field' => 'str',
                            'source' => true,
                            'title' => 'String value',
                        ]
                    )
                );
                $this->addColumn(
                    new TextColumn(
                        [
                            'id' => 'str_value_callback',
                            'field' => 'str',
                            'title' => 'String value (from callback)',
                            'source' => true,
                            'callback' => function(string $v): Markup {
                                return new Markup('<span style="background:lightblue;">' . $this->e($v) . '</span>', 'UTF-8');
                            }
                        ]
                    )
                );
                $this->addColumn(
                    new TextColumn(
                        [
                            'id' => 'str_value_template',
                            'field' => 'str',
                            'title' => 'String value (from template)',
                            'source' => true,
                        ]
                    )
                );
                $this->addColumn(
                    new SelectColumn(
                        [
                            'id' => 'str_value_select',
                            'title' => 'Select',
                            'field' => 'str',
                            'source' => true,
                            'values' => ['foo' => 'Foo', 'bar' => 'Bar'],
                        ]
                    )
                );
                $this->addColumn(
                    new SelectColumn(
                        [
                            'id' => 'str_value_multi_select',
                            'title' => 'Select (multiple)',
                            'field' => 'str',
                            'source' => true,
                            'values' => ['foo' => 'Foo', 'bar' => 'Bar'],
                            'multiple' => true,
                            'submitOnChange' => false,
                        ]
                    )
                );
                $this->addMassAction(new DeleteMassAction());
                $this->addRowAction(new RowAction('Test', 'grid'));

                parent::setSource($source);
            }

            private function e(string $s): string {
                return htmlentities($s, ENT_QUOTES, 'UTF-8');
            }
        };

        return $grid->gridResponse(compact('grid'), 'grid.html.twig');
    }

    private function createTestData(ContainerInterface $container): void
    {
        /** @var Connection $connection */
        $connection = $container->get('doctrine.dbal.default_connection');

        $connection->executeUpdate('DROP TABLE IF EXISTS test_entity');
        $connection->executeUpdate('CREATE TABLE test_entity (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            str VARCHAR(256) DEFAULT NULL
        )');

        $connection->beginTransaction();

        $connection->executeUpdate('INSERT INTO test_entity (str) VALUES (?)', ['<script>alert("ATTACK!")</script>']);

        for ($a = 0; $a < 10; $a++) {
            $connection->executeUpdate('INSERT INTO test_entity (str) VALUES (?)', ['foo']);
            $connection->executeUpdate('INSERT INTO test_entity (str) VALUES (?)', ['bar']);
            $connection->executeUpdate('INSERT INTO test_entity (str) VALUES (?)', ['baz']);
        }

        $connection->executeUpdate('INSERT INTO test_entity (str) VALUES (?)', ['<script>alert("ATTACK!")</script>']);

        $connection->commit();
    }
}
