<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\ShopUser;

class ShopController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Number of shops displayed per page
     */
    private $shops_per_page = 10;

    /**
     * Return Nearby Shops.
     */
    public function nearby(){
        /**
         * User location
         */
        $lat = Auth::user()->latitude;
        $lon = Auth::user()->longitude;

        /**
         * Selecting nearby shops
         */
        return DB::table('shops')
            ->select('shops.*', 'shops.id as id', 'shop_users.like as like')
            // liked shops shouldn’t be displayed on the main page
            // disliked shops are not displayed during the next 2 hours
            ->leftJoin('shop_users', 'shops.id', '=', 'shop_users.shop_id')
            ->whereNull('shop_users.like')
            ->orWhere([
                ['shop_users.like', '=', -1],
                ['shop_users.updated_at', '<=', Carbon::now()->subHour(2)],
            ])
            // shops are orderd by distance from the authenticated user location
            ->orderByRaw("(latitude-$lat)*(latitude-$lat) + (longitude-$lon)*(longitude-$lon)")
            ->paginate($this->shops_per_page)
        ;
    }

    /**
    * Return preferred shops
    */
    public function preferred(){
        
        return DB::table('shops')
            ->select('shops.*', 'shops.id as id', 'shop_users.like as like')
            ->join('shop_users', 'shops.id', '=', 'shop_users.shop_id')
            ->where('shop_users.like', '=', 1)
            ->paginate($this->shops_per_page);
    }

    /**
    * Like a shop
    */
    public function like($shopId){

        $shop_user = ShopUser::firstOrNew(['user_id' => Auth::user()->id, 'shop_id' => $shopId]);
        $shop_user->like = 1;
        $shop_user->save();

        return ['success' => true];
    }

    /**
    * Dislike a shop
    */
    public function dislike($shopId){
        
        $shop_user = ShopUser::firstOrNew(['user_id' => Auth::user()->id, 'shop_id' => $shopId]);
        $shop_user->like = -1;
        $shop_user->save();

        return ['success' => true];
    }

    /**
    * UnLike a shop
    */
    public function unlike($shopId){

        $shop_user = ShopUser::where('user_id', Auth::user()->id) ->where('shop_id', $shopId)->first();
        if($shop_user)
            $shop_user->delete();

        return ['success' => true];
    }
}
