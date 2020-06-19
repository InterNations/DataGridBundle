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

use InterNations\DataGridBundle\Grid\Filter;
use Twig\Markup;

class TextColumn extends Column
{
    private $inputType = 'text';

    public function initialize(array $params): void
    {
        parent::initialize($params);
        $this->setInputType($this->getParam('inputType', 'text'));
    }

    public function getInputType()
    {
        return $this->inputType;
    }

    public function setInputType($inputType)
    {
        $this->inputType = $inputType;
    }

    public function renderFilter(string $gridHash)
    {
        $markup = sprintf(
            '<input type="%s" value="%s" name="%s[%s]"',
            $this->inputType,
            $this->escape($this->data),
            $gridHash,
            $this->getId()
        );

        if ($this->getSize()) {
            $markup .= ' style="width:'.$this->getSize().'px"';
        }

        if ($this->getSubmitOnChange()) {
            $markup .= ' onkeypress="if (event.which === 13){this.form.submit();}"';
        }

        if ($this->data) {
            $markup .= ' class="has-value"';
        }

        $markup .= '/>';

        return new Markup($markup, 'UTF-8');
    }

    public function getFilters()
    {
        return array(new Filter(self::OPERATOR_SUBSTRING, $this->data));
    }

    public function setData($data)
    {
        if (is_string($data))
        {
            $this->data = $data;
        }

        return $this;
    }
}
