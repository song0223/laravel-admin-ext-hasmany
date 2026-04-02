<?php

namespace Encore\HasmanyExtra\Fields;

use Encore\Admin\Form\Field;
use Encore\HasmanyExtra\Support\TableBuilder;
use Closure;
use Illuminate\Support\Arr;

class JsonTable extends Field
{
    /**
     * @var string
     */
    protected $view = 'hasmany-extra::json-table';

    /**
     * @var array
     */
    protected $columnDefinitions = [];

    /**
     * @param string $column
     * @param array $arguments
     */
    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        if (count($arguments) === 1 && $arguments[0] instanceof Closure) {
            $this->buildColumns($arguments[0]);
        }

        if (count($arguments) === 2 && $arguments[1] instanceof Closure) {
            $this->label = $arguments[0];
            $this->buildColumns($arguments[1]);
        }
    }

    /**
     * @return string
     */
    protected function domKey()
    {
        $name = $this->elementName ?: $this->formatName($this->column);

        return preg_replace('/[^A-Za-z0-9_]+/', '_', is_array($name) ? json_encode($name) : $name);
    }

    /**
     * @param array $definitions
     * @return $this
     */
    public function columns(array $definitions)
    {
        $this->columnDefinitions = $definitions;

        return $this;
    }

    /**
     * @param Closure $builder
     * @return void
     */
    protected function buildColumns(Closure $builder)
    {
        $table = new TableBuilder();
        $builder($table);
        $this->columnDefinitions = $table->columns();
    }

    /**
     * @param mixed $value
     * @return array
     */
    public static function parseRows($value)
    {
        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            $value = $value->toArray();
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value) || empty($value)) {
            return [];
        }

        if (Arr::isAssoc($value)) {
            $value = [$value];
        }

        return array_values(array_filter(array_map(function ($row) {
            if (is_string($row)) {
                $decoded = json_decode($row, true);
                $row = is_array($decoded) ? $decoded : [];
            }

            return is_array($row) ? $row : [];
        }, $value)));
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function prepare($value)
    {
        if (!is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $row) {
            if (!is_array($row)) {
                continue;
            }

            $item = [];

            foreach ($this->columnDefinitions as $key => $definition) {
                $item[$key] = trim((string) ($row[$key] ?? ''));
            }

            if (count(array_filter($item, function ($cell) {
                return $cell !== '';
            })) === 0) {
                continue;
            }

            $rows[] = $item;
        }

        return $rows;
    }

    /**
     * @return string
     */
    protected function inputPath()
    {
        $name = $this->elementName ?: $this->formatName($this->column);

        return trim(str_replace(['][', '[', ']'], ['.', '.', ''], $name), '.');
    }

    /**
     * @return array
     */
    protected function getRows()
    {
        $old = old($this->inputPath());
        $value = $old !== null ? $old : $this->value;
        $rows = static::parseRows($value);

        if (empty($rows)) {
            return [];
        }

        if (Arr::isAssoc($rows) && count(array_intersect(array_keys($rows), array_keys($this->columnDefinitions ?: $this->getDefaultColumns()))) > 0) {
            return [$rows];
        }

        return $rows;
    }

    /**
     * @return array
     */
    protected function getDefaultColumns()
    {
        return [
            'name' => ['label' => '名称', 'type' => 'text'],
            'desc' => ['label' => '描述', 'type' => 'textarea'],
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function render()
    {
        if (empty($this->columnDefinitions)) {
            $this->columnDefinitions = $this->getDefaultColumns();
        }

        $this->id = $this->domKey();

        $this->addVariables([
            'rows' => $this->getRows(),
            'json_table_columns' => $this->columnDefinitions,
            'table_id' => 'json-table-'.$this->domKey(),
        ]);

        $this->script = <<<'EOT'
(function () {
    if (window.hasmanyExtraJsonTableBooted) {
        return;
    }

    window.hasmanyExtraJsonTableBooted = true;

    $(document).off('click.hasmanyExtraJsonTableAdd', '.json-table-field .json-table-add');
    $(document).on('click.hasmanyExtraJsonTableAdd', '.json-table-field .json-table-add', function () {
        var wrapper = $(this).closest('.json-table-field')[0];

        if (!wrapper) {
            return;
        }

        var tbody = wrapper.querySelector('tbody');
        var template = wrapper.querySelector('template');

        if (!tbody || !template) {
            return;
        }

        var index = tbody.querySelectorAll('tr').length;
        var html = template.innerHTML.replace(/__INDEX__/g, index);
        tbody.insertAdjacentHTML('beforeend', html);
    });

    $(document).off('click.hasmanyExtraJsonTableRemove', '.json-table-field .json-table-remove');
    $(document).on('click.hasmanyExtraJsonTableRemove', '.json-table-field .json-table-remove', function () {
        var wrapper = $(this).closest('.json-table-field')[0];

        if (!wrapper) {
            return;
        }

        var tbody = wrapper.querySelector('tbody');
        var rows = tbody ? tbody.querySelectorAll('tr') : [];

        if (rows.length <= 1) {
            if (rows.length) {
                rows[0].querySelectorAll('input, textarea').forEach(function (el) {
                    el.value = '';
                });
            }
            return;
        }

        $(this).closest('tr').remove();
    });
})();
EOT;

        return parent::render();
    }
}
