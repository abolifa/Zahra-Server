<?php

namespace App\Http\Controllers\API;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OffersApiController extends Controller
{
    public function index()
    {
        $sections = Section::orderBy('id', 'DESC')->get()->toArray();
        $offers = Offer::orderBy('id', 'DESC')->get();
        $data = array(
            "sections" => $sections,
            "offers" => $offers
        );
        return CommonHelper::responseWithData($data);
    }

    public function save(Request $request)
    {
        Log::info('Request Data:', $request->all());
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,jpg,png,gif',
            'position' => 'required',
            'discount' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $offer = new Offer();

        // Handle file upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
            $offer->image = Storage::disk('public')->putFileAs('offers', $file, $fileName);
        }

        $this->extracted($request, $offer);

        return CommonHelper::responseSuccess("Offer Saved Successfully!");
    }

    public function update(Request $request)
    {
        // Validation logic
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:offers,id',
            'position' => 'required',
            'discount' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $offer = Offer::find($request->id);
        if (!$offer) {
            return CommonHelper::responseError("Offer not found.");
        }



        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($offer->image) {
                @Storage::disk('public')->delete($offer->image);
            }

            $file = $request->file('image');
            $fileName = time() . '_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
            $offer->image = Storage::disk('public')->putFileAs('offers', $file, $fileName);
        }

        $this->extracted($request, $offer);

        return CommonHelper::responseSuccess("Offer Updated Successfully!");
    }



    public function delete(Request $request)
    {

        if (isset($request->id)) {

            $offer = Offer::find($request->id);
            if ($offer) {
                @Storage::disk('public')->delete($offer->image);
                $offer->delete();
                return CommonHelper::responseSuccess("Offer Deleted Successfully!");
            } else {
                return CommonHelper::responseSuccess("Offer Already Deleted!");
            }
        }
    }

    /**
     * @param Request $request
     * @param $offer
     * @return void
     */
    public function extracted(Request $request, $offer): void
    {
        $offer->brand_id = is_numeric($request->brand_id) ? (int)$request->brand_id : null;
        $offer->category_id = is_numeric($request->category_id) ? (int)$request->category_id : null;
        $offer->section_id = is_numeric($request->section_id) ? (int)$request->section_id : null;
        $offer->position = $request->position;
        $offer->discount = $request->discount;

        // Save the updated offer
        $offer->save();
    }
}
