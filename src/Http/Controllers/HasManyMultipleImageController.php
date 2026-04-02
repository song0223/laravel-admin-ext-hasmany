<?php

namespace Encore\HasmanyExtra\Http\Controllers;

use Encore\Admin\Form\Field;
use Encore\HasmanyExtra\Concerns\HandlesHasManyMultipleImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HasManyMultipleImageController extends Controller
{
    use HandlesHasManyMultipleImage;

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $itemId = $request->input('item_id');
        $modelClass = $request->input('model');
        $column = $request->input('column', 'banner');
        $deleteKey = $request->input(Field::FILE_DELETE_FLAG);

        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json(['status' => false, 'message' => 'invalid model'], 422);
        }

        $deleted = $this->deleteHasManyMultipleImage($modelClass, $itemId, $deleteKey, $column);

        if (!$deleted) {
            return response()->json(['status' => false, 'message' => 'record not found'], 404);
        }

        return response()->json(['status' => true]);
    }
}
