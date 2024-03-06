<?php

namespace App\Http\Controllers\Api;

use App\Models\Items;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Item\StoreRequest;

class ItemController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $item = Items::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $item->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        ItemResource::collection($item);
        return GlobalFunction::responseFunction(Message::ITEM_DISPLAY, $item);
    }

    public function store(StoreRequest $request)
    {
        $item = Items::create([
            "code" => $request->code,
            "name" => $request->name,
        ]);

        $uom_collect = new ItemResource($item);

        return GlobalFunction::save(Message::ITEM_SAVE, $uom_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $item = Items::find($id);
        $is_exists = Items::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $item->update([
            "code" => $request->code,
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(Message::ITEM_UPDATE, $item);
    }

    public function destroy($id)
    {
        $item = Items::where("id", $id)
            ->withTrashed()
            ->get();

        if ($item->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $item = Items::withTrashed()->find($id);
        $is_active = Items::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $item->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $item->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $item);
    }
}
