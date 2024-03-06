<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;

class ApproverSettingsController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $approver = ApproverSettings::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })
            ->with("settings_approve")
            ->useFilters()
            ->latest("updated_at")
            ->dynamicPaginate();

        $is_empty = $approver->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::APPROVERS_DISPLAY,
            $approver
        );
    }

    public function store(Request $request)
    {
        $approver = new ApproverSettings([
            "module" => $request["module"],
            "company_id" => $request["company_id"],
            "business_unit_id" => $request["business_unit_id"],
            "department_id" => $request["department_id"],
            "department_unit_id" => $request["department_unit_id"],
            "sub_unit_id" => $request["sub_unit_id"],
            "location_id" => $request["location_id"],
        ]);

        $approver->save();

        $set_approver = $request["settings_approver"];

        foreach ($set_approver as $key => $value) {
            SetApprover::create([
                "approver_settings_id" => $approver->id,
                "department_id" => $approver->department_id,
                "department_unit_id" => $approver->department_unit_id,
                "sub_unit_id" => $approver->sub_unit_id,
                "location_id" => $approver->location_id,
                "approver_id" => $set_approver[$key]["approver_id"],
                "approver_name" => $set_approver[$key]["approver_name"],
                "layer" => $set_approver[$key]["layer"],
            ]);
        }

        return GlobalFunction::save(Message::APPROVERS_SAVE, $approver);
    }
    public function update(Request $request, $id)
    {
        $setting = ApproverSettings::find($id);

        $sub_unit = ApproverSettings::where("id", $id)
            ->get()
            ->first();

        $set_approver = $request["settings_approver"];

        // TAG SETTINGS
        $newTaggedApproval = collect($set_approver)
            ->pluck("id")
            ->toArray();
        $currentTaggedApproval = SetApprover::where("approver_settings_id", $id)
            ->where("department_id", $sub_unit->department_id)
            ->where("department_unit_id", $sub_unit->department_unit_id)
            ->where("sub_unit_id", $sub_unit->sub_unit_id)
            ->where("location_id", $sub_unit->location_id)
            ->get()
            ->pluck("id")
            ->toArray();

        $deletableApproverIds = array_diff(
            $newTaggedApproval,
            $currentTaggedApproval
        );
        if (count($deletableApproverIds) > 0) {
            SetApprover::where("approver_settings_id", $id)
                ->whereIn("id", $deletableApproverIds)
                ->delete();
        }
        foreach ($set_approver as $index => $value) {
            SetApprover::updateOrCreate(
                [
                    "approver_settings_id" => $id,
                    "department_id" => $value["department_id"],
                    "department_unit_id" => $value["department_unit_id"],
                    "sub_unit_id" => $value["sub_unit_id"],
                    "location_id" => $value["location_id"],
                    "approver_id" => $value["approver_id"],
                    "approver_name" => $value["approver_name"],
                ],
                ["layer" => $value["layer"]]
            );
        }

        $setting->update([
            "company_id" => $request["company_id"],
            "business_unit_id" => $request["business_unit_id"],
            "department_id" => $request["department_id"],
            "department_unit_id" => $request["department_unit_id"],
            "sub_unit_id" => $request["sub_unit_id"],
            "location_id" => $request["location_id"],
        ]);

        return GlobalFunction::responseFunction(
            Message::APPROVERS_UPDATE,
            $setting
        );
    }

    public function destroy($id)
    {
        $approver = ApproverSettings::where("id", $id)
            ->withTrashed()
            ->get();

        if ($approver->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approver = ApproverSettings::withTrashed()->find($id);
        $is_active = ApproverSettings::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $approver->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $approver->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::response_function($message, $approver);
    }
}
