<?php

namespace Encore\HasmanyExtra\Fields;

use Encore\Admin\Form\Field\MultipleImage;

class HasManyMultipleImage extends MultipleImage
{
    /**
     * @var string
     */
    protected $view = 'hasmany-extra::hasmany-multiple-image';

    /**
     * Existing file paths in hasMany rows should not be revalidated as uploads.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * @return string
     */
    protected function domKey()
    {
        $name = $this->elementName ?: $this->formatName($this->column);

        return preg_replace('/[^A-Za-z0-9_]+/', '_', is_array($name) ? json_encode($name) : $name);
    }

    /**
     * @return void
     */
    protected function setupDefaultOptions()
    {
        parent::setupDefaultOptions();

        $relation = $this->relationName();
        $itemId = $this->relatedItemId();
        $modelClass = $this->relatedModelClass();

        if (!$relation || !$itemId || !$modelClass) {
            return;
        }

        $this->options['deleteUrl'] = url(config('admin.route.prefix').'/hasmany-extra/delete');
        $this->options['deleteExtraData'] = [
            'item_id' => $itemId,
            'model' => $modelClass,
            'column' => $this->column,
            static::FILE_DELETE_FLAG => '',
            '_token' => csrf_token(),
        ];
    }

    /**
     * @return string|null
     */
    protected function relationName()
    {
        $name = $this->elementName ?: $this->formatName($this->column);

        if (!is_string($name) || !preg_match('/^([^\[]+)\[/', $name, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @return int|null
     */
    protected function relatedItemId()
    {
        $name = $this->elementName ?: $this->formatName($this->column);

        if (!is_string($name) || !preg_match('/^[^\[]+\[([^\]]+)\]\[/', $name, $matches)) {
            return null;
        }

        return ctype_digit($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * @return string|null
     */
    protected function relatedModelClass()
    {
        $relation = $this->relationName();
        $model = $this->form ? $this->form->model() : null;

        if (!$relation || !$model || !method_exists($model, $relation)) {
            return null;
        }

        return get_class($model->{$relation}()->getRelated());
    }

    /**
     * @param string $options
     * @return void
     */
    protected function setupScripts($options)
    {
        $selector = 'input.hasmany-multiple-image-'.$this->domKey();
        $sortSelector = '#'.$this->sortInputId();
        $domKey = $this->domKey();

        $this->script = <<<EOT
$("{$selector}").fileinput({$options});
EOT;

        if ($this->fileActionSettings['showRemove']) {
            $text = [
                'title' => trans('admin.delete_confirm'),
                'confirm' => trans('admin.confirm'),
                'cancel' => trans('admin.cancel'),
            ];

            $this->script .= <<<EOT
$("{$selector}").on('filebeforedelete', function() {

    return new Promise(function(resolve) {

        var remove = resolve;

        swal({
            title: "{$text['title']}",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{$text['confirm']}",
            showLoaderOnConfirm: true,
            cancelButtonText: "{$text['cancel']}",
            preConfirm: function() {
                return new Promise(function(resolve) {
                    resolve(remove());
                });
            }
        });
    });
});
EOT;
        }

        if ($this->fileActionSettings['showDrag']) {
            $this->script .= <<<EOT
var syncPreviewOrder_{$domKey} = function(params) {
    var order = [];

    if (params && params.stack) {
        params.stack.forEach(function (item) {
            if (typeof item.key === 'undefined') {
                return;
            }

            order.push(item.key);
        });
    }

    if (order.length) {
        $("{$sortSelector}").val(JSON.stringify(order));
    }
};

$("{$selector}").on('filesorted', function(event, params) {
    syncPreviewOrder_{$domKey}(params);
});
EOT;
        }

        $this->script .= <<<EOT
var syncPreviewOrderFromDom_{$domKey} = function(input) {
    var order = [];
    var seen = {};
    var previewFrames = $(input)
        .closest('.file-input')
        .find('.file-preview .file-preview-thumbnails > .file-preview-frame.file-preview-initial');

    previewFrames.each(function () {
        var key = $(this).attr('data-fileindex');

        if (typeof key === 'undefined' || seen[key]) {
            return;
        }

        seen[key] = true;
        order.push(key);
    });

    $("{$sortSelector}").val(JSON.stringify(order));
};

$("{$selector}").on('filedeleted fileremoved fileclear', function() {
    syncPreviewOrderFromDom_{$domKey}(this);
});

$("{$selector}").each(function() {
    syncPreviewOrderFromDom_{$domKey}(this);
});
EOT;
    }

    /**
     * @param mixed|string $files
     * @return array
     */
    public function prepare($files)
    {
        if (request()->has(static::FILE_DELETE_FLAG)) {
            return $this->destroy(request(static::FILE_DELETE_FLAG));
        }

        $files = is_array($files) ? $files : [];
        $targets = array_map([$this, 'prepareForeach'], $files);

        return array_merge($this->sortOriginalFiles(), $targets);
    }

    /**
     * @return array
     */
    protected function sortOriginalFiles()
    {
        $original = $this->normalizeOriginalFiles($this->original());
        $order = $this->sortOrder();

        if (empty($original) || empty($order)) {
            return $original;
        }

        $sorted = [];

        foreach ($order as $key) {
            $key = $this->normalizeOrderKey($key);

            if (!array_key_exists($key, $original)) {
                continue;
            }

            $sorted[] = $original[$key];
        }

        return $sorted;
    }

    /**
     * @param mixed $original
     * @return array
     */
    protected function normalizeOriginalFiles($original)
    {
        if (empty($original)) {
            return [];
        }

        if (is_string($original)) {
            $decoded = json_decode($original, true);
            $original = is_array($decoded) ? $decoded : array_filter(explode(',', $original));
        }

        return array_values(array_filter((array) $original));
    }

    /**
     * @return array
     */
    protected function sortOrder()
    {
        $order = request($this->sortInputName(), []);

        if (is_string($order)) {
            $decoded = json_decode($order, true);
            $order = is_array($decoded) ? $decoded : explode(',', $order);
        }

        return is_array($order) ? $order : [];
    }

    /**
     * @param mixed $key
     * @return int
     */
    protected function normalizeOrderKey($key)
    {
        $key = (string) $key;

        if (strpos($key, 'init_') === 0 || strpos($key, 'init-') === 0) {
            $key = substr($key, 5);
        }

        return (int) $key;
    }

    /**
     * @return string
     */
    protected function sortInputName()
    {
        $name = $this->elementName ?: $this->formatName($this->column);

        return preg_replace('/\]$/', '__order]', $name);
    }

    /**
     * @return string
     */
    protected function sortInputId()
    {
        return 'hasmany_multiple_image_sort_'.$this->domKey();
    }

    /**
     * @return mixed
     */
    public function render()
    {
        $this->options($this->options);

        $this->addVariables([
            'sort_input_name' => $this->sortInputName(),
            'sort_input_id' => $this->sortInputId(),
        ]);

        return parent::render();
    }
}
