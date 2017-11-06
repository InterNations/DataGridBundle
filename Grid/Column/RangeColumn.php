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
use Twig\Markup;

class RangeColumn extends Column
{
    private $inputType = 'text';

    public function __initialize(array $params)
    {
        parent::__initialize($params);
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
        $result = '<div class="range-column-filter">';
        $result .= '<input class="';

        if ($this->data['from']) {
            $result .= 'has-value ';
        }

        $result .= 'first-filter" placeholder="From:" type="'.$this->inputType.'" value="'.$this->escape($this->data['from']).'" name="'.$gridHash.'['.$this->getId().'][from]"';

        $keypressHandler = 'onkeypress="if (event.which == 13){this.form.submit();}"';
        if ($this->getSubmitOnChange()) {
            $result .=  $keypressHandler;
        }

        if ($this->getSize()) {
            $result .= ' style="width:'.$this->getSize().'px"';
        }

        $result .= '/><br/>';
        $result .= '<input class="';

        if ($this->data['to']) {
            $result .= 'has-value ';
        }

        $result .= 'second-filter" placeholder="To:" type="'.$this->inputType.'" value="'.$this->escape($this->data['to']).'" name="'.$gridHash.'['.$this->getId().'][to]"';

        if ($this->getSubmitOnChange()) {
            $result .=  $keypressHandler;
        }

        if ($this->getSize()) {
            $result .= ' style="width:'.$this->getSize().'px"';
        }

        $result .= '/>';
        $result .= '</div>';

        return new Markup($result, 'UTF-8');
    }

    public function getFilters()
    {
        $result = array();

        if ($this->data['from'] != '')
        {
           $result[] =  new Filter(self::OPERATOR_GTE, '\''.$this->data['from'].'\'');
        }

        if ($this->data['to'] != '')
        {
           $result[] =  new Filter(self::OPERATOR_LTE, '\''.$this->data['to'].'\'');
        }

        return $result;
    }

    public function setData($data)
    {
        $this->data = array('from' => '', 'to' => '');

        if (is_array($data))
        {
            if (isset($data['from']) && is_string($data['from']))
            {
                $this->data['from'] = $data['from'];
            }

            if (isset($data['to']) && is_string($data['to']))
            {
               $this->data['to'] = $data['to'];
            }
        }

        return $this;
    }

    public function getData()
    {
        $result = array();

        if ($this->data['from'] != '')
        {
           $result['from'] =  $this->data['from'];
        }

        if ($this->data['to'] != '')
        {
           $result['to'] =  $this->data['to'];
        }

        return $result;
    }

    public function isFiltered()
    {
        return $this->data['from'] != '' || $this->data['to'] != '';
    }

    public function getType()
    {
        return 'range';
    }
}
