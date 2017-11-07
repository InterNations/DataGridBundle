<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Grid\Source;

use Psr\Container\ContainerInterface;
use Sorien\DataGridBundle\Grid\Column\Column;
use Sorien\DataGridBundle\Grid\Columns;
use Sorien\DataGridBundle\Grid\Helper\ColumnsIterator;
use Sorien\DataGridBundle\Grid\Rows;
use Sorien\DataGridBundle\Grid\Row;

class Entity extends Source
{
    private const TABLE_ALIAS = '_a';
    private const COUNT_ALIAS = '__count';

    /** @var \Doctrine\ORM\EntityManager  */
    protected $manager;

    /** @var \Doctrine\ORM\QueryBuilder */
    private $query;

    private $entityName;

    private $joins = [];

    public function __construct(string $entityName)
    {
        $this->entityName = $entityName;
    }

    public function initialise(ContainerInterface $container): void
    {
        $this->manager = $container->get('doctrine')->getManager();
    }

    private function getFieldName(Column $column, bool $withAlias = true): string
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

    private function normalizeOperator(string $operator): string
    {
        return $operator === COLUMN::OPERATOR_REGEXP ? 'like': $operator;
    }

    private function normalizeValue(string $operator, string $value): string
    {
        if ($operator === COLUMN::OPERATOR_REGEXP) {
            preg_match('@/\.\*([^/]+)\.\*/@', $value, $matches);

            return '\'%' . $matches[1] . '%\'';
        }

        return $value;
    }

    public function execute(ColumnsIterator $columns, int $page = 0, int $limit = 0): Rows
    {
        $this->query = $this->manager->createQueryBuilder();
        $this->query->from($this->entityName, self::TABLE_ALIAS);

        $where = $this->query->expr()->andX();

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
                            $this->query->expr()->$operator(
                                $this->getFieldName($column, false),
                                '?' . $parameterIndex
                            )
                        );

                        $parameter = trim($this->normalizeValue($filter->getOperator(), $filter->getValue()), '\'');

                        $this->query->setParameter($parameterIndex++, $parameter);
                    }
                } elseif ($column->getFiltersConnection() === Column::DATA_DISJUNCTION) {
                    $sub = $this->query->expr()->orX();

                    foreach ($column->getFilters() as $filter) {
                        $operator = $this->normalizeOperator($filter->getOperator());

                        $sub->add(
                            $this->query->expr()->$operator(
                                $this->getFieldName($column, false),
                                '?' . $parameterIndex
                            )
                        );

                        $parameter = trim($this->normalizeValue($filter->getOperator(), $filter->getValue()), '\'');

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

            //call overridden prepareRow or associated closure
            if (($modifiedRow = $this->prepareRow($row)) !== null) {
                $result->addRow($modifiedRow);
            }
        }

        return $result;
    }

    public function getTotalCount(Columns $columns): int
    {
        $query = $this->prepareCountQuery(clone $this->query);

        $query->select($this->getFieldName($columns->getPrimaryColumn()));
        $query->setFirstResult(null);
        $query->setMaxResults(null);

        $qb = $this->manager->createQueryBuilder();

        $qb->select($qb->expr()->count(self::COUNT_ALIAS . '.' . $columns->getPrimaryColumn()->getField()));
        $qb->from($this->entityName, self::COUNT_ALIAS);
        $qb->where(
            $qb->expr()->in(self::COUNT_ALIAS . '.' . $columns->getPrimaryColumn()->getField(), $query->getDQL())
        );

        //copy existing parameters.
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
                throw new \Exception(sprintf('No %s found for id %s', $this->entityName, $id));
            }

            $this->manager->remove($object);
        }

        $this->manager->flush();
    }
}
