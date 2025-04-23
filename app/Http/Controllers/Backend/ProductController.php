<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\ProductDataTable;
use App\DataTables\ProductStockZeroTable;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ImageUploadTrait;
    public function showStockZero(ProductStockZeroTable $dataTable)
    {
        // 查詢庫存為 0 的產品
        return $dataTable->render('admin.product.productlist.stock_zero');
    }


    public function index(ProductDataTable $dataTable)
    {
        return $dataTable->render('admin.product.productList.index');
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.product.productList.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:3000'],
            'name' => ['required', 'max:200'],
            'category' => ['required'],
            'price' => ['required'],
            'qty' => ['required'],
            'short_description' => ['required', 'max:600'],
            'long_description' => ['required'],
            'seo_title' => ['nullable','max:200'],
            'seo_description' => ['nullable','max:250'],
            'status' => ['required']
        ]);

        /** Handle the image upload */
        $imagePath = $this->uploadImage($request, 'image', 'uploads');

        $product = new Product();
        $product->thumb_image = $imagePath;
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->category_id = $request->category;
        $product->qty = $request->qty;
        $product->short_description = $request->short_description;
        $product->long_description = $request->long_description;
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->offer_price = $request->offer_price;
        $product->status = $request->status;
        $product->product_type = $request->product_type;
        $product->offer_start_date = $request->offer_start_date;
        $product->offer_end_date = $request->offer_end_date;
        $product->seo_title = $request->seo_title;
        $product->seo_description = $request->seo_description;
        $product->save();


        return redirect()->route('admin.product.productList.index')->with('success','建立成功');

    }
        /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::all();
        return view('admin.product.productList.edit', compact('product', 'categories'));
    }
        /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // 錯誤提醒待辦
        $request->validate([
            'image' => ['nullable', 'image', 'max:3000'],
            'name' => ['required', 'max:200'],
            'category' => ['required'],
            'price' => ['required'],
            'qty' => ['required'],
            'short_description' => ['required', 'max: 600'],
            'long_description' => ['required'],
            'seo_title' => ['nullable','max:200'],
            'seo_description' => ['nullable','max:250'],
            'status' => ['required']
        ]);

        $product = Product::findOrFail($id);

        /** Handle the image upload */
        $imagePath = $this->updateImage($request, 'image', 'uploads', $product->thumb_image);

        $product->thumb_image = empty(!$imagePath) ? $imagePath : $product->thumb_image;
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->category_id = $request->category;
        $product->qty = $request->qty;
        $product->short_description = $request->short_description;
        $product->long_description = $request->long_description;
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->offer_price = $request->offer_price;
        $product->offer_start_date = $request->offer_start_date;
        $product->offer_end_date = $request->offer_end_date;
        $product->status = $request->status;
        $product->product_type = $request->product_type;
        $product->seo_title = $request->seo_title;
        $product->seo_description = $request->seo_description;
        $product->save();

        if ($request->has('redirect_from')) {
            $redirectFrom = $request->redirect_from;
    
            if ($redirectFrom == 'productList') {
                return redirect()->route('admin.product.productList.index')->with('success', '產品更新成功！');
            } elseif ($redirectFrom == 'stock-zero') {
                return redirect()->route('admin.product.stockZero')->with('success', '產品更新成功！');
            }
        }
        return redirect()->back()->with('success','更新成功');
        // return redirect()->route('admin.product.productList.index')->with('success','更新成功');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        // if(OrderProduct::where('product_id',$product->id)->count() > 0){
        //     return response(['status' => 'error', 'message' => 'This product have orders can\'t delete it.']);
        // }

        /** Delte the main product image */
        $this->deleteImage($product->thumb_image);

        /** Delete product gallery images */
        // $galleryImages = ProductImageGallery::where('product_id', $product->id)->get();
        // foreach($galleryImages as $image){
        //     $this->deleteImage($image->image);
        //     $image->delete();
        // }


        $product->delete();

        return response(['status' => 'success', 'message' => '刪除成功!']);
    }

    public function changeStatus(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->status = $request->status == 'true' ? 1 : 0;
        $product->save();

        return response(['message' => '更新成功!']);
    }


}
