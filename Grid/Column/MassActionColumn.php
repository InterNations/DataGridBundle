<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Grid\Column;

use Sorien\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Markup;

class MassActionColumn extends Column
{
    const ID = '__action';

    private $gridHash;

    public function __construct($gridHash)
    {
        $this->gridHash = $gridHash;
        parent::__construct(array('id' => self::ID, 'title' => '', 'size' => 15, 'sortable' => false, 'source' => false, 'align' => 'center'));
    }

    public function renderFilter(string $gridHash): string
    {
        return '<input type="checkbox" class="grid-mass-selector" onclick="'.$gridHash.'_mark_visible(this.checked); return true;"/>';
    }

    public function renderCell($value, Row $row, UrlGeneratorInterface $urlGenerator)
    {
        return new Markup(
            sprintf(
                '<input type="checkbox" class="action" value="1" name="%s[%s][%s]"/>',
                $this->gridHash,
                self::ID,
                $row->getPrimaryFieldValue()
            ),
            'UTF-8'
        );
    }
}
