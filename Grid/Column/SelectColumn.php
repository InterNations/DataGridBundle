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

use Sorien\DataGridBundle\Grid\Filter;
use Sorien\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Markup;

class SelectColumn extends Column
{
    const BLANK = '_default';

    private $values = [];
    private $defaults = [];
    private $multiple = false;

    public function initialize(array $params): void
    {
        $this->data = [];
        parent::initialize($params);
        $this->values = $this->getParam('values', []);
        $this->defaults = (array) $this->getParam('defaults', []);
        $this->multiple = $this->getParam('multiple', false);
    }

    public function renderFilter(string $gridHash): string
    {
        $result = '<option value="' . $this::BLANK . '"></option>';

        $data = [];
        if ($this->data != self::BLANK) {
            $data = $this->data ?: $this->defaults;
        }

        foreach ($this->values as $key => $value) {
            if (is_array($data) && in_array($key, $data)) {
                $result .= '<option value="' . $key . '" selected="selected">' . $value . '</option>';
            } else {
                $result .= '<option value="' . $key . '">' . $value . '</option>';
            }
        }

        $markup = '<select'
            . ($this->multiple ? ' multiple="multiple"': '')
            . ' name="'
            . $gridHash
            . '['
            . $this->getId()
            . '][]";';

        if ($this->getSubmitOnChange()) {
            $markup .= ' onchange="this.form.submit();"';
        }

        if ($this->getSize()) {
            $markup .= ' style="width:' . $this->getSize() . 'px"';
        }

        if ($data) {
            $markup .= ' class="has-value"';
        }

        $markup .= '>' . $result . '</select>';

        return new Markup($markup, 'UTF-8');
    }

    public function setData($data)
    {
        $data = (array) $data;

        if (in_array(self::BLANK, $data)) {
            $this->data = self::BLANK;

            return $this;
        }

        foreach ($data as $key => $value) {
            if (!key_exists($value, $this->values)) {
                unset($data[$key]);
            }
        }

        if ($this->data === self::BLANK) {
            $this->data = $data;
        } else {
            $this->data = array_merge((array) $this->data, $data);
        }

        return $this;
    }

    public function getFilters()
    {
        $filters = [];

        $values = [];
        if ($this->data != self::BLANK) {
            $values = $this->data ?: $this->defaults;
        }

        foreach ($values as $value) {
            $filters[] = new Filter(self::OPERATOR_EQ, '\'' . $value . '\'');
        }

        return $filters;
    }

    public function getFiltersConnection()
    {
        return self::DATA_DISJUNCTION;
    }

    public function isFiltered()
    {
        return $this->data != self::BLANK && (!empty($this->data) || !empty($this->defaults));
    }

    public function getValues()
    {
        return $this->values;
    }

    public function renderCell($value, Row $row, UrlGeneratorInterface $urlGenerator)
    {
        if (key_exists((string) $value, $this->values)) {
            $value = $this->values[$value];
        }

        return parent::renderCell($value, $row, $urlGenerator);
    }
}
