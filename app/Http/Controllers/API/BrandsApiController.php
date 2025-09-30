<?php

namespace App\Http\Controllers\API;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BrandsApiController extends Controller
{
    public function index()
    {
        $brands = Brand::orderBy('id', 'DESC')->get()->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'image' => $brand->image,
                'image_url' => $brand->image ? asset('storage/' . $brand->image) : null,
                'cover_image' => $brand->cover_image,
                'cover_image_url' => $brand->cover_image ? asset('storage/' . $brand->cover_image) : null,
                'status' => $brand->status,
                'created_at' => $brand->created_at,
                'updated_at' => $brand->updated_at,
            ];
        });

        return CommonHelper::responseWithData($brands);
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->status = 1;

        $image = '';
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
            $image = Storage::disk('public')->putFileAs('brand', $file, $fileName);
        }

        $coverImage = '';
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $fileName = time() . '_cover_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
            $coverImage = Storage::disk('public')->putFileAs('brand', $file, $fileName);
        }

        $brand->image = $image;
        $brand->cover_image = $coverImage;
        $brand->save();

        return CommonHelper::responseSuccess("Brand Saved Successfully!");
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        if (isset($request->id)) {
            $brand = Brand::find($request->id);
            if (!$brand) {
                return CommonHelper::responseError("Brand not found.");
            }

            $brand->name = $request->name;
            $brand->status = $request->status;

            if ($request->hasFile('image')) {
                @Storage::disk('public')->delete($brand->image);
                $file = $request->file('image');
                $fileName = time() . '_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
                $image = Storage::disk('public')->putFileAs('brand', $file, $fileName);
                $brand->image = $image;
            }

            if ($request->hasFile('cover_image')) {
                @Storage::disk('public')->delete($brand->cover_image);
                $file = $request->file('cover_image');
                $fileName = time() . '_cover_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
                $coverImage = Storage::disk('public')->putFileAs('brand', $file, $fileName);
                $brand->cover_image = $coverImage;
            }

            $brand->save();
        }

        return CommonHelper::responseSuccess("Brand Updated Successfully!");
    }

    public function delete(Request $request)
    {
        if (isset($request->id)) {
            $brand = Brand::find($request->id);
            if ($brand) {
                @Storage::disk('public')->delete($brand->image);
                @Storage::disk('public')->delete($brand->cover_image); // Delete cover image
                $brand->delete();
                return CommonHelper::responseSuccess("Brand Deleted Successfully!");
            } else {
                return CommonHelper::responseSuccess("Brand Already Deleted!");
            }
        }
    }

    public function getBrands()
    {
        $brands = Brand::orderBy('id', 'ASC')->where('status', 1)->get();
        return CommonHelper::responseWithData($brands);
    }
}
