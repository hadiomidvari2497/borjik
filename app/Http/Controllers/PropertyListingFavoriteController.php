<?php
namespace App\Http\Controllers;
use App\Models\PropertyListing;
use Illuminate\Http\Request;
class PropertyListingFavoriteController extends Controller
{
 public function index(Request $request){$listings=$request->user()->favoritePropertyListings()->published()->with('user')->latest('property_listing_favorites.created_at')->paginate(12);$listings->getCollection()->each(fn(PropertyListing $listing)=>$listing->setAttribute('is_favorited',true));return view('properties.favorites',compact('listings'));}
 public function toggle(Request $request,PropertyListing $listing){abort_unless($listing->status==='active'&&(!$listing->expires_at||$listing->expires_at->isFuture()),404);$favorites=$request->user()->favoritePropertyListings();$saved=$favorites->where('property_listings.id',$listing->id)->exists();if($saved){$favorites->detach($listing->id);$message='آگهی از نشان‌شده‌ها حذف شد.';}else{$favorites->syncWithoutDetaching([$listing->id]);$message='آگهی به نشان‌شده‌ها اضافه شد.';}return back()->with('status',$message);}
}
