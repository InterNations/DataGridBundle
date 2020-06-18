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

use InvalidArgumentException;
use InterNations\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DateColumn extends TextColumn
{
    private $format;

    public function initialize(array $params): void
    {
        parent::initialize($params);
        $this->format = $this->getParam('format', 'Y-m-d H:i:s');
    }

    public function renderFilter(string $gridHash)
    {
        return '';
    }

    public function renderCell($value, Row $row, UrlGeneratorInterface $urlGenerator)
    {
        if ($value !== null) {
            if (is_string($value)) {
                $value = new \DateTime($value);
            }

            if ($value instanceof \DateTime) {
                return parent::renderCell($value->format($this->format), $row, $urlGenerator);
            }

            throw new InvalidArgumentException('Date Column value have to be DataTime object');
        }

        return '';
    }
}
