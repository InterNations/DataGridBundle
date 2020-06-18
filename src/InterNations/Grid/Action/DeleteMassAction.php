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

class DeleteMassAction extends MassAction
{
    public function __construct($confirm = false)
    {
        parent::__construct('Delete', function (array $ids) { $this->deleteAction($ids); }, $confirm);
    }
}
