<?php

namespace Encore\HasmanyExtra\Support;

class TableBuilder
{
    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @param string $type
     * @param array $arguments
     * @return $this
     */
    public function __call($type, $arguments)
    {
        $column = $arguments[0] ?? '';
        $label = $arguments[1] ?? '';

        if ($column === '') {
            return $this;
        }

        $this->columns[$column] = [
            'label' => $label ?: $column,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return $this->columns;
    }
}
