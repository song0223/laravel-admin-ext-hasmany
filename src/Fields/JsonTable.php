<?php

namespace Encore\HasmanyExtra\Fields;

use Encore\Admin\Admin;
use Encore\Admin\Form\Field;
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

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value) && Arr::isAssoc($value)) {
            $hasDefinedColumns = count(array_intersect(array_keys($value), array_keys($this->columnDefinitions ?: $this->getDefaultColumns()))) > 0;

            if ($hasDefinedColumns) {
                $value = [$value];
            }
        }

        if (!is_array($value) || empty($value)) {
            return [];
        }

        return array_values(array_map(function ($row) {
            if (is_string($row)) {
                $decoded = json_decode($row, true);
                $row = is_array($decoded) ? $decoded : [];
            }

            return is_array($row) ? $row : [];
        }, $value));
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

        $domKey = $this->domKey();
        $script = <<<EOT
(function () {
    var wrapper = document.getElementById('json-table-{$domKey}');
    if (!wrapper) {
        return;
    }

    var tbody = wrapper.querySelector('tbody');
    var addButton = wrapper.querySelector('.json-table-add');
    var template = wrapper.querySelector('template');

    if (!tbody || !addButton || !template) {
        return;
    }

    addButton.addEventListener('click', function () {
        var index = tbody.querySelectorAll('tr').length;
        var html = template.innerHTML.replace(/__INDEX__/g, index);
        tbody.insertAdjacentHTML('beforeend', html);
    });

    wrapper.addEventListener('click', function (event) {
        if (!event.target.classList.contains('json-table-remove')) {
            return;
        }

        var rows = tbody.querySelectorAll('tr');
        if (rows.length <= 1) {
            rows[0].querySelectorAll('input, textarea').forEach(function (el) {
                el.value = '';
            });
            return;
        }

        event.target.closest('tr').remove();
    });
})();
EOT;

        Admin::script($script);

        return parent::render();
    }
}
