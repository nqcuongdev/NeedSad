<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Products;
use App\Slides;
use App\Category;
use App\Product_Type;
use App\BlogModel;
use App\Order;
use App\OrderDetails;
use App\Guest;
use App\User;
use Auth;
use Admin;
use Cart;

use Illuminate\Support\Facades\Hash;
class AdminController extends Controller
{
    public function getDashboard()
    {
        $order = Order::where('status','=',1)
                        ->select('oder.total')->first();
        $member = User::where('status','=',1)->count();
        $guest = User::all()->count();

        $user = $member + $guest;

        $products = Products::where('status','=',1)->count();
        return view('admin.dashboard',compact('order','user','products'));
    }

    public function getSlides(){
        $slides = Slides::all();
        return view('admin.slides-index',compact('slides'));
    }

    public function postSlides(Request $request){
        $slides = new Slides;
        $slides->small_title = $request->small_title;
        $slides->title = $request->title;
        $slides->small_text = $request->small_text;
        $slides->price = $request->price;
        $slides->btn_link = $request->btn_link;

        //Process upload file
        $image = $request->image;
        $image->move(public_path('/Smarttech/images/slides'), $image->getClientOriginalName());
        $link = 'Smarttech/images/slides/' . $image->getClientOriginalName();
        $slides->image = $link;
        $slides->save();
        return redirect('admin/slides');
    }

    public function getProducts()
    {
        $products = Products::join('product_type','products.products_type','=','product_type.id')
                            ->select('products.id','products.name',
                            'products.price','sale_price','products.image',
                            'products.technical_description','products.description','products.available','products.status','products.new_product',
                            'product_type.id as type_id','product_type.name_type')
                            ->orderBy('products.id')->get();
        return view('admin.products',compact('products'));
    }

    public function getAddProducts(){
        $product_type = Product_Type::where('status','=',1)->get();
         return view('admin.add-products',compact('product_type'));
        }
    public function postProducts(Request $request)
    {
        $products = new Products;
        $products->name = $request->name;
        $products->products_type = $request->products_type;
        $products->technical_description = $request->technical_description;
        $products->description = $request->description;
        $products->price = $request->price;
        $products->sale_price = $request->sale_price;

        //Process upload file
        $image_pr = $request->image;
        $image_pr->move(public_path('/Smarttech/images/products'), $image_pr->getClientOriginalName());
        $link = 'Smarttech/images/products/' . $image_pr->getClientOriginalName();
        $products->image = $link;

        //Multi file
        $image_details = array();
        for ($i = 1; $i <= 6; $i++) {
            $name = 'image_'.$i;
            if ($request->hasFile($name)){
                $file[$i] = $request->$name;
                $file[$i]->move(public_path('/Smarttech/images/products/product-details/'), $file[$i]->getClientOriginalName());
                $links[$i] = 'Smarttech/images/products/product-details/' . $file[$i]->getClientOriginalName();
                array_push($image_details, $links[$i]);
            }
        }
        $image_details = json_encode($image_details);
        $products->details_image = $image_details;

        $products->available = $request->available;
        $products->status = 1;
        $products->new_product = $request->new_product;

        $products->save();
        return redirect('admin/products');
    }

    public function getEditProducts($id){
        $products = Products::where([['products.id','=',$id],['products.status','=','1']])
                            ->join('product_type','products.products_type','=','product_type.id')
                            ->select('products.id','products.name',
                            'products.price','sale_price','products.image','products.details_image',
                            'products.technical_description','products.description','product_type.id as type_id',
                            'product_type.name_type')->first();
        $product_type = Product_Type::where('status','=',1)->get();
        return view('admin.edit-products',compact('products','product_type'));
    }

    public function postEditProducts(Request $request, $id){
        $products = Products::find($id);
        $products->name = isset($request->name) ? $request->name : $products->name;
        $products->products_type = isset($request->products_type) ? $request->products_type : $products->products_type;
        $products->technical_description = isset($request->technical_description) ? $request->technical_description : $products->technical_description;
        $products->description = isset($request->description) ? $request->description : $products->description;
        $products->price = isset($request->price) ? $request->price : $products->price;
        $products->sale_price = isset($request->sale_price) ? $request->sale_price : $products->sale_price;

        if($request->has('image')){
            //Process upload file
            $image_pr = $request->image;
            $image_pr->move(public_path('/Smarttech/images/products'), $image_pr->getClientOriginalName());
            $link = 'Smarttech/images/products/' . $image_pr->getClientOriginalName();
            $products->image = $link;
            $products->image = $link;
        }else{
            $products->image = $products->image;
        }

        $image_details = array();
        for ($i = 1; $i <= 6; $i++) {
            $name = 'image_'.$i;
            if ($request->hasFile($name)){
                $file[$i] = $request->$name;
                $file[$i]->move(public_path('/Smarttech/images/products/product-details/'), $file[$i]->getClientOriginalName());
                $links[$i] = 'Smarttech/images/products/product-details/' . $file[$i]->getClientOriginalName();
                array_push($image_details, $links[$i]);
            }
        }
        $image_details = json_encode($image_details);
        $products->details_image = $image_details;

        $products->available = isset($request->available) ? $request->available : $products->available;
        $products->status = 1;
        $products->new_product = isset($request->new_product) ? $request->new_product : $products->new_product;
        $products->save();
        return redirect('admin/products');
    }

    public function getDisableProducts($id){
        $products = Products::find($id);
        $products->name = $products->name;
        $products->products_type = $products->products_type;
        $products->technical_description = $products->technical_description;
        $products->description = $products->description;
        $products->price = $products->price;
        $products->sale_price = $products->sale_price;
        $products->image = $products->image;

        $products->available = $products->available;
        $products->status = 0;
        $products->new_product = $products->new_product;
        $products->save();
        return redirect('admin/products');
    }

    public function getActiveProducts($id){
        $products = Products::find($id);
        $products->name = $products->name;
        $products->products_type = $products->products_type;
        $products->technical_description = $products->technical_description;
        $products->description = $products->description;
        $products->price = $products->price;
        $products->sale_price = $products->sale_price;
        $products->image = $products->image;

        $products->available = $products->available;
        $products->status = 1;
        $products->new_product = $products->new_product;
        $products->save();
        return redirect('admin/products');
    }

    public function getCategory(){
        $type = Product_Type::join('category','product_type.id_category','=','category.id')
                ->select('product_type.*','category.name_category')
                ->get();
        return view('admin.category-management',compact('type'));
    }

    public function getAPICategory() {
        $category = json_encode(Category::all());
        return $category;
    }

    public function postCategory(Request $request){
        $category = Category::create($request->all());
        return redirect('admin/category');
    }

    public function postEditCategory(Request $request, $id){
            dd($request->name_category);
            $category = Category::find($id);
            $category->name_category = $request->name_category;
            $category->status = 1;
            $category->save();
            return "success";
    }

    public function getDisableCategory($id){
        $category = Category::find($id);
        $category->name_category = $category->name_category;
        $category->status = 0;
        $category->save();
        return redirect()->back();
    }

    public function getActiveCategory($id){
        $category = Category::find($id);
        $category->name_category = $category->name_category;
        $category->status = 1;
        $category->save();
        return redirect()->back();
    }

    public function postType(Request $request){
        $type = new Product_Type;
        $type->name_type = $request->name_type;
        $type->id_category = $request->id_category;
        $type->status = 1;
        $type->save();
        return redirect()->back();
    }

    public function postEditType(Request $request){
        if($request->ajax()){
            $type = Product_Type::find($request->id);
            $type->name_type = $request->name;
            $type->id_category = $request->category;
            $type->status = 1;
            $type->save();
            return "success";
        }
    }

    public function getDisableType($id){
        $type = Product_Type::find($id);
        $type->name_type = $type->name_type;
        $type->id_category = $type->id_category;
        $type->status = 0;
        $type->save();
        return redirect()->back();
    }

    public function getActiveType($id){
        $type = Product_Type::find($id);
        $type->name_type = $type->name_type;
        $type->id_category = $type->id_category;
        $type->status = 1;
        $type->save();
        return redirect()->back();
    }

    public function getAdminLogout(){
        Auth::guard('admins')->logout();
        return redirect('/');
    }

    public function getBlogManagement(){
        $blog = BlogModel::all();
        return view('admin.blog-list', compact('blog'));
    }

    public function getAddBlog(){ return view('admin.add-blog'); }

    public function postAddBlog(Request $request){
        $blog = new BlogModel;

        $blog->author = $request->author;
        $blog->title = $request->title;
        $blog->content = $request->content;

        //Process upload file
        $image = $request->image;
        $image->move(public_path('/Smarttech/images/blog'), $image->getClientOriginalName());
        $link = 'Smarttech/images/blog/' . $image->getClientOriginalName();
        $blog->image = $link;

        $blog->status = 1;
        $blog->save();
        return redirect('admin/blog-management');
    }

    public function getDisableBlog($id){
        $blog = BlogModel::find($id);
        $blog->author = $blog->author;
        $blog->title = $blog->title;
        $blog->content = $blog->content;
        $blog->image = $blog->image;
        $blog->status = 0;
        $blog->save();
        return redirect('admin/blog-management');
    }

    public function getActiveBlog($id){
        $blog = BlogModel::find($id);
        $blog->author = $blog->author;
        $blog->title = $blog->title;
        $blog->content = $blog->content;
        $blog->image = $blog->image;
        $blog->status = 1;
        $blog->save();
        return redirect('admin/blog-management');
    }

    public function getDeleteBlog($id){
        $blog = BlogModel::find($id);
        $blog->delete();
        return redirect('admin/blog-management');
    }

    public function getOrderManagement(){
        $orders = Order::all();
        return view('admin.order-management',compact('orders'));
    }

    public function getOrderDetails($id){
        $order = Order::where('oder.id','=',$id)
                        ->join('order_details','order_details.oder_id','=','oder.id')
                        ->join('products','products.id','=','order_details.product_id')
                        ->first();

        $str_id = $order->id_customer;
        if(is_int($str_id) != 0){
            $customer_id = substr($str_id,5,strlen($str_id));
            $customer = Guest::where('id','=',$customer_id)
                    ->select('name as customer_name','phone','address','email')
                    ->get();
        }else {
            $customer_id = $str_id;
            $customer = User::where('id','=',$customer_id)
                    ->select('name as customer_name','phone','address','email')
                    ->first();
        }
                    
            return view('admin.order-details',compact('order','customer'));
        }

    public function getSuccessStatus($id){
        $order = Order::find($id);
        $order->id = $order->id;
        $order->qty = $order->qty;
        $order->shipping = $order->shipping;
        $order->total = $order->total;
        $order->status = 2;
        $order->save();
        return redirect()->back();
    }

    public function getCancelStatus($id){
        $order = Order::find($id);
        $order->id = $order->id;
        $order->qty = $order->qty;
        $order->shipping = $order->shipping;
        $order->total = $order->total;
        $order->status = 3;
        $order->save();
        return redirect()->back();
    }

    //Error
    public function getDeleteOrder($id){
        // $order = Order::join('order_details','order_details.oder_id','=','oder.id')
        //                 ->where('oder.id','=',$id)
        //                 ->get();
        
        return "Chưa cho xóa nà !!!";
    }

    public function getUserManagement(){
        $user = User::all();
        $guest = Guest::all();
        return view('admin.user-management',compact('user','guest'));
    }
}
