<?php

namespace App\Repositories;

use App\Tenant;
use App\Product;
use App\Attribute;
use App\ProductImage;
use App\ProductAttribute;
use App\Traits\UploadAble;
use Illuminate\Http\UploadedFile;
use App\Contracts\ProductContract;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 *
 */
class ProductRepository implements ProductContract
{

  use UploadAble;


  function __construct(Product $model)
  {
    $this->model = $model;
  }

    public function listProducts(string $order = 'id', string $sort = 'desc', array $columns = ['*'])
    {
      return Product::all($columns, $order, $sort);
    }

      public function findProductById(int $id)
      {
        try {
          return $this->model->findOrFail($id);
        }
        catch(ModelNotFoundException $e) {
          throw new ModelNotFoundException($e);
        }
      }

      public function findProductByCategory(int $id)
      {
        try {
          return $this->model->with('categories')->whereHas('categories', function($query) use ($id){
            $query->where('id', $id);
          });
        }
        catch(ModelNotFoundException $e) {
          throw new ModelNotFoundException($e);
        }
      }

      public function createProduct(array $params)
      {
        try {
          $collection = collect($params);

          $featured = $collection->has('featured') ? 1: 0;
          $status = $collection->has('status') ? 1: 0;

          $image = null;



          $merge = $collection->merge(compact('featured', 'status'));

          $product = new Product($merge->all());

          $product->save();
          if ($collection->has('images') && ($params['images'] instanceof  UploadedFile)) {
            $image = $this->uploadOne($params['images'], 'products/'.$params['name']);

            $productImage = new ProductImage([
              'full' => tenant_asset($image)
            ]);

            $product->images()->save($productImage);
          }



          if($collection->has('categories')){
            $cats = json_decode($params['categories']);
            foreach($cats as $category){

            $product->categories()->sync($category);
          }

        }



         if($collection->has('attributes')){

           $attr = json_decode($params['attributes']);
           foreach($attr as $assignedAttr){
             foreach($assignedAttr->value as $value){
             $product->attributes()->create([
               'attribute_id' => $assignedAttr->name,
               'value' => $value,
               'price' => $assignedAttr->price
             ])->save();
           }
            /* ProductAttribute::create([
               'attribute_id' => $assignedAttr->name,
               'value' => $assignedAttr->value,
               'price' => $assignedAttr->price,
               'product_id' => json_encode($product->id)
             ]); */

           }

          }

          return response(array('message' => 'Product Created Successfully.', 'data' => $product), 200);
        }

          catch (QueryException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
          }
        }



      public function updateProduct(array $params)
   {
       $product = $this->findProductById($params['id']);

       $collection = collect($params)->except('_token');

       $featured = $collection->has('featured') ? 1 : 0;
       $status = $collection->has('status') ? 1 : 0;

       $merge = $collection->merge(compact('status', 'featured'));

       $product->update($merge->all());

       if ($collection->has('categories')) {
           $product->categories()->sync($params['categories']);
       }


          foreach($params['attributes'] as $attr){

            $product->attributes()->firstOrCreate(['value' => $attr['value'], 'attribute_id' => $attr['attribute_id']]);

        }



       return $params;

   }


   public function deleteProduct($id)
   {
       $product = $this->findProductById($id);
    $images = ProductImage::where('product_id', $id)->first();

      if ($images) {
         $this->deleteOne($images->full);
         $images->delete();
       }

       $product->delete();

       return $product;
   }


}
