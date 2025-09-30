<?php

namespace App\Http\Controllers\API\Customer;

use App\Helpers\CommonHelper;
use App\Helpers\ProductHelper;
use App\Http\Controllers\Controller;
use App\Http\Repository\CategoryRepository;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Offer;
use App\Models\Pincode;
use App\Models\Product;
use App\Models\Section;
use App\Models\Slider;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShopApiController extends Controller
{

    public $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getShopData(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ], [
            'latitude.required' => 'The latitude field is required.',
            'longitude.required' => 'The longitude field is required.'
        ]);
        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }
        try {
            $user_id = $request->user('api-customers') ? $request->user('api-customers')->id : '';
            $sort = ($request->sort) ?? 'id';
            $limit = ($request->limit) ?? 12;
            $offset = ($request->offset) ?? 0;


            if ($sort == 'new') {
                $sort = 'created_at DESC';
                $price = 'MIN(discounted_price)';
                $price_sort = 'pv.discounted_price  ASC';
            } elseif ($sort == 'old') {
                $sort = 'created_at ASC';
                $price = 'MIN(discounted_price)';
                $price_sort = 'pv.discounted_price  ASC';
            } elseif ($sort == 'high') {
                $sort = 'price DESC';
                $price = 'MAX(if(pv.discounted_price > 0 && pv.discounted_price != 0, pv.discounted_price, pv.price))';
                $price_sort = 'if(pv.discounted_price > 0 && pv.discounted_price != 0, pv.discounted_price, pv.price) DESC';
            } elseif ($sort == 'low') {
                $sort = 'price ASC';
                $price = 'MIN(if(pv.discounted_price > 0 && pv.discounted_price != 0, pv.discounted_price, pv.price))';
                $price_sort = 'if(pv.discounted_price > 0 && pv.discounted_price != 0, pv.discounted_price, pv.price) ASC';
            } else {
                $sort = 'p.row_order ASC';
                $price = 'MIN(discounted_price)';
                $price_sort = 'pv.id  ASC';
            }


            $where = '';
            $where1 = '';

            if (isset($request->search) && $request->search != '') {
                $search = $request->search;
                if (empty($where)) {
                    $where = " (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR p.`image` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%')";
                } else {
                    $where .= " AND (p.`id` like '%" . $search . "%' OR p.`name` like '%" . $search . "%' OR p.`image` like '%" . $search . "%' OR p.`slug` like '%" . $search . "%' OR p.`description` like '%" . $search . "%')";
                }
            }

            if (isset($request->section) && intval($request->section)) {
                $section = Section::select('*')->where('sections.id', '=', intval($request->section))->first();

                if (!empty($section)) {
                    $cate_ids = $section->category_ids;
                    $product_ids = $section->product_ids;

                    if ($section->product_type == 'all_products') {
                        if (empty($section->category_ids)) {
                            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY product_id DESC";
                        } else {
                            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 AND category_id IN($cate_ids) ORDER BY product_id DESC";
                        }
                    } elseif ($section->product_type == 'new_added_products') {
                        if (empty($section->category_ids)) {
                            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 ORDER BY product_id DESC";
                        } else {
                            $sql = "SELECT id as product_id FROM `products` WHERE status = 1 AND category_id IN($cate_ids) ORDER BY product_id DESC";
                        }
                    } elseif ($section->product_type == 'products_on_sale') {
                        if (empty($section->category_ids)) {
                            $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
                        } else {
                            $sql = "SELECT p.id as product_id FROM `products` p LEFT JOIN product_variant pv ON p.id=pv.product_id WHERE p.status = 1 AND p.category_id IN($cate_ids) AND pv.discounted_price > 0 AND pv.price > pv.discounted_price ORDER BY p.id DESC";
                        }
                    } elseif ($section->product_type == 'most_selling_products') {
                        if (empty($section->category_ids)) {
                            $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' GROUP BY pv.id,p.id ORDER BY total DESC";
                        } else {
                            $sql = "SELECT p.id as product_id,oi.product_variant_id, COUNT(oi.product_variant_id) AS total FROM order_items oi LEFT JOIN product_variant pv ON oi.product_variant_id = pv.id LEFT JOIN products p ON pv.product_id = p.id WHERE oi.product_variant_id != 0 AND p.id != '' AND p.category_id IN ($cate_ids) GROUP BY pv.id,p.id ORDER BY total DESC";
                        }
                    } else {
                        $product_ids = $product_ids;
                    }

                    if ($section->product_type != 'custom_products' && !empty($section->product_type)) {
                        $products = \DB::select(\DB::raw($sql));
                        $rows = $tempRow = array();
                        foreach ($products as $product) {
                            $tempRow['product_id'] = $product->product_id;
                            $rows[] = $tempRow;
                        }
                        $pro_id = array_column($rows, 'product_id');
                        $product_ids = implode(",", $pro_id);
                    }

                    if (empty($where)) {
                        $where = " p.id IN ($product_ids) AND p.status = 1 AND pv.stock >= 0";
                    } else {
                        $where .= " AND p.id IN ($product_ids) AND p.status = 1 AND pv.stock >= 0";
                    }

                } else {
                    $output = array(
                        'status' => 0,
                        'message' => 'Section Not created.'
                    );
                    return false;
                }
            }

            if (isset($request->category) && trim($request->category) != "") {
                $category_ids = explode(',', $request->category);
                $category_id = implode(',', $category_ids);
                if (empty($where)) {
                    $where = " p.category_id IN($category_id)";
                } else {
                    $where .= " AND p.category_id IN($category_id)";
                }
            }

            if (isset($request->pincode) && trim($request->pincode) != "") {
                $pincode = Pincode::select('*')->where('pincode.id', '=', $request->pincode)->first();
                if (!isset($pincode) || empty($pincode)) {
                    $output = array(
                        'error' => true,
                        'message' => 'Invalid pincode.'
                    );
                }
                // get pincode id
                $pincode_id = $pincode->id;
                if (isset($request->section) && intval($request->section)) {
                    if (empty($where)) {
                        $where .= " ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) or p.type = 'all' and p.id IN ($product_ids)) OR ((p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes) and p.id IN ($product_ids))) ";
                    } else {
                        $where .= " AND ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) or p.type = 'all' and p.id IN ($product_ids)) OR ((p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes) and p.id IN ($product_ids))) ";
                    }
                } else {
                    if (empty($where)) {
                        $where .= " ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) or p.type = 'all') OR ((p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes))) ";
                    } else {
                        $where .= " AND ((p.type='included' and FIND_IN_SET('$pincode_id', p.pincodes)) or p.type = 'all') OR ((p.type='excluded' and NOT FIND_IN_SET('$pincode_id', p.pincodes))) ";
                    }
                }
            }


            if (empty($where)) {
                $where .= " p.status = 1  ";
            } else {
                $where .= " AND p.status = 1  ";
            }
            $seller_ids = CommonHelper::getSellerIds($request->latitude, $request->longitude);

            $productSql = Product::from('products as p')->select(
                DB::raw('COUNT(p.id) AS total'),
                DB::raw('MIN((select MIN(if(discounted_price > 0, discounted_price, price)) from product_variants where product_variants.product_id = p.id)) as min_price'),
                DB::raw('MAX((select MAX(if(discounted_price > 0, discounted_price, price)) from product_variants where product_variants.product_id = p.id)) as max_price')
            )->leftJoin('product_variants as pv', 'pv.product_id', '=', 'p.id')->whereIn('p.seller_id', $seller_ids);

            $productResult = $productSql->whereRaw($where)->first();
            $total = $productResult->total;
            $min_price = $productResult->min_price;
            $max_price = $productResult->max_price;

            $productResult1 = $productSql->first();
            $all_total = $productResult1->total;
            $all_min_price = $productResult1->min_price;
            $all_max_price = $productResult1->max_price;

            $sql = Product::with(['images', 'brand'])->from("products as p")->select("p.*", "c.name as category_name",

                "t.title as tax_title", "t.percentage as tax_percentage",
                DB::raw("ceil(((price-discounted_price)/price)*100) as cal_discount_percentage"),
                DB::raw("(SELECT ceil(if(t.percentage > 0 , " . $price . " + ( " . $price . " * (t.percentage / 100)), " . $price . ")) FROM product_variants as pv WHERE pv.product_id=p.id) as price"),
                DB::raw("(select MIN(if(discounted_price > 0, discounted_price, price)) from product_variants where product_variants.product_id = p.id) as min_price"),
                DB::raw("(select MAX(if(discounted_price > 0, discounted_price, price)) from product_variants where product_variants.product_id = p.id) as max_price")
            )
                ->leftJoin("categories as c", "p.category_id", "=", "c.id")
                ->Join("product_variants as pv", "pv.product_id", "=", "p.id")
                ->leftJoin("taxes as t", "p.tax_id", "=", "t.id")
                ->whereIn('p.seller_id', $seller_ids)
                ->whereRaw($where)
                ->groupBy("p.id");

            if (isset($request->min_price) && isset($request->max_price) && intval($request->max_price)) {
                $sql = $sql->havingRaw(" min_price > " . intval(intval($request->min_price) - 1) . " and max_price < " . intval(intval($request->max_price) + 1));
            }

            if (isset($request->discount_filter) && intval($request->discount_filter)) {
                if (empty($request->min_price) && empty($request->max_price)) {
                    $sql = $sql->having("cal_discount_percentage ", ">= ", $request->discount_filter);
                } else {
                    $sql = $sql->havingRaw("cal_discount_percentage >= " . $request->discount_filter);
                }
            }
            $products = $sql->orderByRaw($sort)->skip($offset)->take($limit)->get();
            $products = $products->makeHidden(['image', 'images']);
            $total = $sql->count();
            $product = array();
            foreach ($products as $key => $row) {

                $row->price = ($row->price == 0) ? 0 : $row->price;
                if ($row->brand_id) {
                    $brand = Brand::find($row->brand_id);
                    $row->brand_name = $brand ? $brand->name : null;
                } else {
                    $row->brand_name = "";
                }
                $row->cal_discount_percentage = (!empty($row->cal_discount_percentage)) ? $row->cal_discount_percentage : "";
                if (!empty($user_id)) {
                    $favorite = Favorite::select("id")->where('product_id', '=', $row->id)->where('user_id', '=', $user_id)->first();
                    if (!empty($favorite)) {
                        $row->is_favorite = true;
                    } else {
                        $row->is_favorite = false;
                    }
                } else {
                    $row->is_favorite = false;
                }
                $variants = $row->variants;
                foreach ($variants as $subkey => $variant) {
                    $image = DB::table('product_images')
                        ->where('product_variant_id', $variant->id)
                        ->value('image');

                    $variant->image = $image ? asset('storage/' . $image) : '';

                    $taxed = ProductHelper::getTaxableAmount($variant->id);
                    $variant->discounted_price = CommonHelper::doubleNumber($taxed->taxable_discounted_price ?? $variant->discounted_price);
                    $variant->price = CommonHelper::doubleNumber($taxed->taxable_price ?? $variant->price);
                    $variant->taxable_amount = CommonHelper::doubleNumber($taxed->taxable_amount);

                    if (!empty($user_id)) {
                        $cart = Cart::select("qty as cart_count")->where('product_variant_id', '=', $variant->id)->where('user_id', '=', $user_id)->first();
                        $variant->cart_count = $cart ? $cart->cart_count : "0";
                    } else {
                        $variant->cart_count = "0";
                    }
                }
                $row->variants = $variants;
                $product[$key] = $row;
            }
        } catch (\Exception $e) {
            Log::info("Shop api Error : " . $e->getMessage());
            throw $e;
            return CommonHelper::responseError("Something Went Wrong!");
        }

        $seller_ids = CommonHelper::getSellerIds($request->latitude, $request->longitude);
        $user_id = $request->user('api-customers') ? $request->user('api-customers')->id : 0;
        $sections = CommonHelper::getSectionWithProduct($seller_ids, $user_id);
        $sliders = Slider::where('status', 1)->orderBy('id', 'DESC')->get();
        $sliders = $sliders->makeHidden(['image', 'product', 'category', 'created_at', 'updated_at', 'status']);
        foreach ($sliders as $key => $slider) {
            $sliders[$key]->slider_url = $sliders[$key]->slider_url ?? "";
            $sliders[$key]->type_id = $sliders[$key]->type_id ? intval($sliders[$key]->type_id) : 0;
        }

        $offers = Offer::orderBy('id', 'DESC')->get();
        $offers = $offers->makeHidden(['image']);
        $is_category_section_in_homepage = CommonHelper::getIsCategorySectionInHomepage();
        $is_brand_section_in_homepage = CommonHelper::getIsBrandSectionInHomepage();
        $output = array(
            'sliders' => $sliders,
            'offers' => $offers,
            'sections' => $sections,
            'is_category_section_in_homepage' => $is_category_section_in_homepage,
            'is_brand_section_in_homepage' => $is_brand_section_in_homepage,
        );

        if ($is_category_section_in_homepage && $is_category_section_in_homepage == 1) {
            $count_category_section_in_homepage_empty = CommonHelper::getCountCategorySectionInHomepage();
            $categories = Category::where('status', 1)
                ->where('parent_id', 0)
                ->where('status', 1)
                ->orderBy('row_order', 'ASC')
                ->limit($count_category_section_in_homepage_empty)
                ->get(['id', 'name', 'subtitle', 'image']);
            $categories = $categories->makeHidden(['image']);
            $output['categories'] = $categories->toArray();
        }
        if ($is_brand_section_in_homepage && $is_brand_section_in_homepage == 1) {
            $count_brand_section_in_homepage_empty = CommonHelper::getCountBrandSectionInHomepage();
            $brands = Brand::select('brands.id', 'brands.name', 'brands.image', 'brands.cover_image')
                ->orderBy('id', 'ASC')
                ->where('status', 1)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('products')
                        ->whereColumn('products.brand_id', 'brands.id');
                });

            $brands = $brands->get()->map(function ($brand) {
                $brand->image_url = url('storage/' . $brand->image); // Add full URL for image
                $brand->cover_image = url('storage/' . $brand->cover_image); // Add full URL for cover image
                return $brand;
            });

            $brands = $brands->makeHidden(['created_at', 'updated_at', 'image', 'status']);
            $output['brands'] = $brands->toArray();
        }
        return CommonHelper::responseWithData($output);
    }
}
