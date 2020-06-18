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

interface MassActionInterface
{
    public function getTitle(): string;
    public function getCallback();
    public function getConfirm(): bool;
}
