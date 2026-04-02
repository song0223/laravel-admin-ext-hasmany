<?php

namespace Encore\HasmanyExtra\Fields;

use Encore\Admin\Form;
use Encore\Admin\Form\NestedForm as BaseNestedForm;
use Encore\HasmanyExtra\Form\NestedForm;
use Illuminate\Support\Arr;

class HasMany extends \Encore\Admin\Form\Field\HasMany
{
    /**
     * @param array $input
     * @return array
     */
    public function prepare($input)
    {
        $form = $this->buildNestedForm($this->column, $this->builder);
        $prepared = $form->setOriginal($this->original, $this->getKeyName())->prepare($input);
        $originalRecords = $this->originalRecordsByKey();

        $imageColumns = [];

        foreach ($form->fields() as $field) {
            if ($field instanceof HasManyMultipleImage) {
                $imageColumns[] = $field->column();
            }
        }

        if (empty($imageColumns)) {
            return $prepared;
        }

        foreach ($input as $key => $record) {
            foreach ($imageColumns as $column) {
                $prepared[$key] = HasManyMultipleImage::applyPreparedOrder(
                    $prepared[$key] ?? [],
                    (array) $record,
                    Arr::get($originalRecords, "{$key}.{$column}"),
                    $column
                );
            }
        }

        return $prepared;
    }

    /**
     * @return array
     */
    protected function originalRecordsByKey()
    {
        $records = [];
        $relatedKey = $this->getKeyName();

        foreach ((array) $this->original as $index => $record) {
            if (!is_array($record)) {
                continue;
            }

            $key = $record[$relatedKey] ?? $index;
            $records[$key] = $record;
        }

        return $records;
    }

    /**
     * @param string $column
     * @param \Closure $builder
     * @param null $model
     * @return BaseNestedForm
     */
    protected function buildNestedForm($column, \Closure $builder, $model = null)
    {
        $form = new NestedForm($column, $model);

        $form->setForm($this->form);

        call_user_func($builder, $form);

        $form->hidden($this->getKeyName());

        $form->hidden(BaseNestedForm::REMOVE_FLAG_NAME)->default(0)->addElementClass(BaseNestedForm::REMOVE_FLAG_CLASS);

        return $form;
    }

    /**
     * @param string $templateScript
     * @return string
     */
    protected function replaceTemplateKeyScript($templateScript)
    {
        $defaultKey = BaseNestedForm::DEFAULT_KEY_NAME;

        return <<<EOT
    var currentScript = {$templateScript};
    var scriptTag = document.createElement('script');
    scriptTag.type = 'text/javascript';
    scriptTag.text = currentScript.replace(/{$defaultKey}/g, index);
    document.body.appendChild(scriptTag);
    document.body.removeChild(scriptTag);
EOT;
    }

    /**
     * @param string $templateScript
     * @return void
     */
    protected function setupScriptForDefaultView($templateScript)
    {
        parent::setupScriptForDefaultView($this->replaceTemplateKeyScript(json_encode($templateScript)));
    }

    /**
     * @param string $templateScript
     * @return void
     */
    protected function setupScriptForTabView($templateScript)
    {
        parent::setupScriptForTabView($this->replaceTemplateKeyScript(json_encode($templateScript)));
    }

    /**
     * @param string $templateScript
     * @return void
     */
    protected function setupScriptForTableView($templateScript)
    {
        parent::setupScriptForTableView($this->replaceTemplateKeyScript(json_encode($templateScript)));
    }
}
