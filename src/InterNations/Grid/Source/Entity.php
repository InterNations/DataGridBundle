<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Grid\Source;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;
use InterNations\DataGridBundle\Grid\Column\Column;
use InterNations\DataGridBundle\Grid\Columns;
use InterNations\DataGridBundle\Grid\Helper\ColumnsIterator;
use InterNations\DataGridBundle\Grid\Row;
use InterNations\DataGridBundle\Grid\Rows;
use RuntimeException;
use function array_shift;
use function count;
use function explode;
use function preg_match;
use function sprintf;
use function strpos;
use function trim;

class Entity extends Source
{
    public const TABLE_ALIAS = '_a';
    public const COUNT_ALIAS = '__count';

    /** @var EntityManagerInterface */
    protected $manager;

    /** @var QueryBuilder */
    protected $query;

    /** @var string e.g Cms:Page */
    protected $entityName;

    /** @var array */
    private $joins;

    public function __construct(string $entityName)
    {
        $this->entityName = $entityName;
        $this->joins = [];
    }

    public function initialise(ContainerInterface $container): void
    {
        $this->manager = $container->get('doctrine')->getManager();
    }

    protected function getFieldName($column, $withAlias = true): string
    {
        $name = $column->getField();

        if (strpos($name, '.') === false) {
            return self::TABLE_ALIAS . '.' . $name;
        }

        $parent = self::TABLE_ALIAS;
        $elements = explode('.', $name);

        while ($element = array_shift($elements)) {
            if (count($elements) > 0) {
                $this->joins['_' . $element] = $parent . '.' . $element;
                $parent = '_' . $element;
                $name = $element;
            } else {
                $name .= '.' . $element;
            }
        }

        if ($withAlias) {
            return '_' . $name . ' as ' . $column->getId();
        }

        return '_' . $name;
    }

    private function normalizeOperator($operator)
    {
        return $operator === Column::OPERATOR_SUBSTRING ? 'like' : $operator;
    }

    private function normalizeValue(string $operator, string $value): string
    {
        $value = trim($value);

        if ($operator === Column::OPERATOR_SUBSTRING) {
            return '%' . $value . '%';
        }

        return $value;
    }

    public function execute(ColumnsIterator $columns, int $page = 0, int $limit = 0): Rows
    {
        $this->query = $this->manager->createQueryBuilder();
        $this->query->from($this->entityName, self::TABLE_ALIAS);

        $where = $this->query->expr()->andx();

        $parameterIndex = 0;
        $sorted = false;

        foreach ($columns as $column) {
            $this->query->addSelect($this->getFieldName($column));

            if ($column->isSorted() && !$column->isDefaultSort()) {
                $this->query->orderBy($this->getFieldName($column, false), $column->getOrder());
                $sorted = true;
            } elseif (!$sorted && $column->isSorted() && $column->isDefaultSort()) {
                $this->query->orderBy($this->getFieldName($column, false), $column->getOrder());
            }

            if ($column->isFiltered()) {
                if ($column->getFiltersConnection() === Column::DATA_CONJUNCTION) {
                    foreach ($column->getFilters() as $filter) {
                        $operator = $this->normalizeOperator($filter->getOperator());

                        $where->add(
                            $this->query->expr()->{$operator}(
                                $this->getFieldName($column, false),
                                '?' . $parameterIndex
                            )
                        );

                        $parameter = $this->normalizeValue($filter->getOperator(), $filter->getValue());

                        $this->query->setParameter($parameterIndex++, $parameter);
                    }
                } elseif ($column->getFiltersConnection() === Column::DATA_DISJUNCTION) {
                    $sub = $this->query->expr()->orx();

                    foreach ($column->getFilters() as $filter) {
                        $operator = $this->normalizeOperator($filter->getOperator());

                        $sub->add(
                            $this->query->expr()->{$operator}(
                                $this->getFieldName($column, false),
                                '?' . $parameterIndex
                            )
                        );

                        $parameter = $this->normalizeValue($filter->getOperator(), $filter->getValue());

                        $this->query->setParameter($parameterIndex++, $parameter);
                    }
                    $where->add($sub);
                }
                $this->query->where($where);
            }
        }

        foreach ($this->joins as $alias => $field) {
            $this->query->leftJoin($field, $alias);
        }

        if ($page > 0) {
            $this->query->setFirstResult($page * $limit);
        }

        if ($limit > 0) {
            $this->query->setMaxResults($limit);
        }

        //call overridden prepareQuery or associated closure
        $query = $this->prepareQuery(clone $this->query);
        $items = $query->getQuery()->getResult();

        // hydrate result
        $result = new Rows();

        foreach ($items as $item) {
            $row = new Row();

            foreach ($item as $key => $value) {
                $row->setField($key, $value);
            }

            foreach ($columns as $column) {
                if (isset($item[$column->getField()])) {
                    $row->setField($column->getId(), $item[$column->getField()]);
                }
            }

            // Call overridden prepareRow or associated closure
            $modifiedRow = $this->prepareRow($row);
            if ($modifiedRow !== null) {
                $result->addRow($modifiedRow);
            }
        }

        return $result;
    }

    public function getTotalCount(Columns $columns): int
    {
        $query = $this->prepareCountQuery(clone $this->query);

        if (!$query->getDQLPart('groupBy')) {
            // no need for a subquery - do a simple count on a primary field
            return (int) $query
                ->resetDQLPart('orderBy')
                ->setMaxResults(null)
                ->setFirstResult(null)
                ->select($query->expr()->countDistinct(self::TABLE_ALIAS . '.' . $columns->getPrimaryColumn()->getField()))
                ->getQuery()
                ->getSingleScalarResult();
        }

        $query->select($this->getFieldName($columns->getPrimaryColumn()));
        $query->setFirstResult(null);
        $query->setMaxResults(null);

        $qb = $this->manager->createQueryBuilder();

        $qb->select($qb->expr()->count(self::COUNT_ALIAS . '.' . $columns->getPrimaryColumn()->getField()));
        $qb->from($this->entityName, self::COUNT_ALIAS);
        $qb->where(
            $qb->expr()->in(self::COUNT_ALIAS . '.' . $columns->getPrimaryColumn()->getField(), $query->getDQL())
        );

        $qb->setParameters($query->getParameters());

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getHash(): string
    {
        return $this->entityName;
    }

    public function delete(array $ids): void
    {
        $repository = $this->manager->getRepository($this->entityName);

        foreach ($ids as $id) {
            $object = $repository->find($id);

            if (!$object) {
                throw new RuntimeException(sprintf('No %s found for id %s', $this->entityName, $id));
            }

            $this->manager->remove($object);
        }

        $this->manager->flush();
    }
}
