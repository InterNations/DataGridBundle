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

class MassAction implements MassActionInterface
{
    private $title;
    private $callback;
    private $confirm;
    private $parameters = [];
    private $group;

    public function __construct(
        string $title,
        $callback = null,
        bool $confirm = false,
        array $parameters = [],
        $group = null
    )
    {
        $this->title = $title;
        $this->callback = $callback;
        $this->confirm = $confirm;
        $this->parameters = $parameters;
        $this->group = $group;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCallback($callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setConfirm(bool $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function getConfirm(): bool
    {
        return $this->confirm;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setGroup(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }
}
