<?php

namespace Encore\HasmanyExtra\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HandlesHasManyMultipleImage
{
    /**
     * @param mixed $value
     * @param bool $withUrl
     * @return array
     */
    protected function parseHasManyMultipleImageValue($value, $withUrl = false)
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            $images = $value;
        } else {
            $images = json_decode($value, true);

            if (!is_array($images)) {
                $images = array_filter(explode(',', $value));
            }
        }

        $images = array_values(array_filter($images));

        if (!$withUrl) {
            return $images;
        }

        return array_map(function ($image) {
            if (preg_match('/^https?:\/\//i', $image)) {
                return $image;
            }

            return env('IMG_URL').$image;
        }, $images);
    }

    /**
     * @param mixed $key
     * @return int
     */
    protected function normalizeHasManyMultipleImageKey($key)
    {
        $key = (string) $key;

        if (strpos($key, 'init_') === 0 || strpos($key, 'init-') === 0) {
            $key = substr($key, 5);
        }

        return (int) $key;
    }

    /**
     * @param array $images
     * @param mixed $order
     * @return array
     */
    protected function sortHasManyMultipleImageByOrder(array $images, $order)
    {
        if (empty($images) || empty($order)) {
            return $images;
        }

        if (is_string($order)) {
            $order = json_decode($order, true) ?: explode(',', $order);
        }

        if (!is_array($order) || empty($order)) {
            return $images;
        }

        $indexed = array_values($images);
        $sorted = [];
        $usedIndexes = [];

        foreach ($order as $key) {
            $index = $this->normalizeHasManyMultipleImageKey($key);

            if (!array_key_exists($index, $indexed)) {
                continue;
            }

            $sorted[] = $indexed[$index];
            $usedIndexes[$index] = true;
        }

        foreach ($indexed as $index => $image) {
            if (isset($usedIndexes[$index])) {
                continue;
            }

            $sorted[] = $image;
        }

        return $sorted;
    }

    /**
     * @param string $requestKey
     * @param string $relatedModelClass
     * @param string $foreignKey
     * @param mixed $parentId
     * @param string $column
     * @param string $orderKey
     * @return void
     */
    protected function syncHasManyMultipleImageOrder(
        $requestKey,
        $relatedModelClass,
        $foreignKey,
        $parentId,
        $column = 'banner',
        $orderKey = 'banner__order'
    ) {
        $items = request()->input($requestKey, []);

        if (!is_array($items) || empty($items) || !class_exists($relatedModelClass)) {
            return;
        }

        foreach ($items as $itemKey => $item) {
            $itemId = $item['id'] ?? $itemKey;
            $order = $item[$orderKey] ?? null;

            if (!$order || !is_numeric($itemId)) {
                continue;
            }

            /** @var Model|null $related */
            $related = $relatedModelClass::query()
                ->where($foreignKey, $parentId)
                ->where('id', (int) $itemId)
                ->first();

            if (!$related) {
                continue;
            }

            $images = $this->parseHasManyMultipleImageValue($related->getRawOriginal($column));
            $sorted = $this->sortHasManyMultipleImageByOrder($images, $order);

            if ($sorted !== $images) {
                $related->update([$column => $sorted]);
            }
        }
    }

    /**
     * @param string $relatedModelClass
     * @param mixed $itemId
     * @param mixed $deleteKey
     * @param string $column
     * @return bool
     */
    protected function deleteHasManyMultipleImage($relatedModelClass, $itemId, $deleteKey, $column = 'banner')
    {
        if (!$itemId || !class_exists($relatedModelClass)) {
            return false;
        }

        /** @var Model|null $related */
        $related = $relatedModelClass::query()->find($itemId);

        if (!$related) {
            return false;
        }

        $images = $this->parseHasManyMultipleImageValue($related->getRawOriginal($column));
        $index = $this->normalizeHasManyMultipleImageKey($deleteKey);

        if (!array_key_exists($index, $images)) {
            return true;
        }

        unset($images[$index]);

        $related->update([
            $column => array_values($images),
        ]);

        return true;
    }
}
