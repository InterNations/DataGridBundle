<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Grid\Column;

use InterNations\DataGridBundle\Grid\Action\RowAction;
use InterNations\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Markup;

class ActionsColumn extends Column
{
    private $rowActions;

    public function __construct(string $column, string $title, array $rowActions = [], array $params = [])
    {
        $this->rowActions = $rowActions;
        parent::__construct(
            array_merge($params, ['id' => $column, 'title' => $title, 'sortable' => false, 'source' => false])
        );
    }

    public function renderCell($value, Row $row, UrlGeneratorInterface $urlGenerator)
    {
        $return = '';

        /* @var $action RowAction */
        foreach ($this->rowActions as $action) {
            $routeParameters = array_merge(
                [$row->getPrimaryField() => $row->getPrimaryFieldValue()],
                $action->getRouteParameters()
            );

            $route = $action->getRoute();
            if (is_callable($route)) {
                $url = $route($urlGenerator, $routeParameters, $row);
            } else {
                $url = $urlGenerator->generate($route, $routeParameters);
            }

            $return .= "<a href='" . $url;

            if ($action->getConfirm()) {
                $return .= "' onclick=\"return confirm('" . $action->getConfirmMessage() . "');\"";
            }

            $return .= "' target='" . $action->getTarget() . "'";


            foreach ($action->getAttributes() as $key => $value) {
                $return .= ' ' . $key . '="' . $value . '"';
            }

            $return .= '>' . $action->getTitle() . '</a> ';
        }

        return new Markup($return, 'UTF-8');
    }

    public function renderFilter(string $gridHash): string
    {
        if ($this->getSubmitOnChange()) {
            return '';
        }

        return new Markup(sprintf('<input name="%s[submit]" type="submit" value="Filter"/>', $gridHash), 'UTF-8');
    }

    public function setRowActions(array $rowActions)
    {
        $this->rowActions = $rowActions;
    }
}
