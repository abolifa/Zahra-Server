<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed|string $brand_id
 * @method static select(string $string, string $string1, string $string2, string $string3, string $string4, string $string5, string $string6, \Illuminate\Database\Query\Expression $raw, \Illuminate\Database\Query\Expression $raw1, \Illuminate\Database\Query\Expression $raw2, \Illuminate\Database\Query\Expression $raw3, \Illuminate\Database\Query\Expression $raw4, string $string7, string $string8, string $string9, string $string10, string $string11)
 */
class Product extends Model
{
    //use HasFactory,SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'name', 'slug', 'description', 'deals', 'row_order', 'image',
        'tax_id', 'brand_id', 'seller_id', 'tags', 'type', 'category_id',
        'indicator', 'manufacturer', 'made_in', 'tax_included_in_price',
        'return_status', 'return_days', 'cancelable_status', 'till_status',
        'cod_allowed', 'total_allowed_quantity', 'is_unlimited_stock',
        'is_approved', 'status', 'fssai_lic_no',
    ];

    protected $appends = ['image_url'];

    protected $hidden=['created_at','updated_at','deleted_at'];

    public function seller(){

        return $this->belongsTo(Seller::class,'seller_id','id');
    }

    public function tax(){
        return $this->belongsTo(Tax::class,'tax_id','id');
    }

    public function madeInCountry(){
        return $this->belongsTo(Country::class,'made_in','id');
    }

    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

      public function brand(){
        return $this->belongsTo(Brand::class,'brand_id','id');
    }

    public function variants(){

        return $this->hasMany(ProductVariant::class,'product_id','id');
    }

    public function images(){

        return $this->hasMany(ProductImages::class,'product_id','id')
            ->where('product_variant_id',0);
    }


    public function getImageUrlAttribute(){

        if($this->image){
            //$image_url = \Storage::url($this->image);
            $image_url = asset('storage/'.$this->image);
            //$this->image;
        }else{
            //$this->image = '';
            $image_url = '';
        }
        return $image_url;
    }
}
