<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Grid\Action;

class RowAction implements RowActionInterface
{
    private $title;
    private $route;
    private $confirm;
    private $confirmMessage;
    private $target;
    private $column = '__actions';
    private $routeParameters = array();
    private $attributes = array();

    /**
     * Default MassAction constructor
     *
     * @param string $title Title of the mass action
     * @param string|Closure $route Route to the row action
     * @param boolean $confirm Show confirm message if true
     * @param string $target Set the target of this action (_slef,_blank,_parent,_top)
     * @return \InterNations\DataGridBundle\Grid\Action\MassAction
     */
    public function __construct($title, $route = null, $confirm = false, $target = '_self', array $attributes = array())
    {
        $this->title = $title;
        $this->route = $route;
        $this->confirm = $confirm;
        $this->confirmMessage = 'Do you want to '.strtolower($title).' this row?';
        $this->target = $target;
        $this->attributes = $attributes;
    }

    /**
     * Set action title
     *
     * @param $title
     * @return \InterNations\DataGridBundle\Grid\Action\MassAction
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * get action title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set action route
     *
     * @param  $route
     * @return \InterNations\DataGridBundle\Grid\Action\RowAction
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * get action route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set action confirm
     *
     * @param  $confirm
     * @return \InterNations\DataGridBundle\Grid\Action\MassAction
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;

        return $this;
    }

    /**
     * get action confirm
     *
     * @return boolean
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * Set action confirmMessage
     *
     * @param  $confirmMessage
     * @return \InterNations\DataGridBundle\Grid\Action\MassAction
     */
    public function setConfirmMessage($confirmMessage)
    {
        $this->confirmMessage = $confirmMessage;

        return $this;
    }

    /**
     * get action confirmMessage
     *
     * @return boolean
     */
    public function getConfirmMessage()
    {
        return $this->confirmMessage;
    }

    /**
     * Set action target
     *
     * @param  $target
     * @return \InterNations\DataGridBundle\Grid\Action\MassAction
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * get action target
     *
     * @return boolean
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set action column
     *
     * @param  $column
     * @return \InterNations\DataGridBundle\Grid\Action\MassAction
     */
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * get action column
     *
     * @return boolean
     */
    public function getColumn()
    {
        return $this->column;
    }

    public function setRouteParameters(array $routeParameters)
    {
        $this->routeParameters = $routeParameters;
    }

    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}
