<?php
namespace Tests\Feature;
use App\Models\PropertyListing;use App\Models\User;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
class PropertyListingFavoriteTest extends TestCase{use RefreshDatabase;
public function test_user_can_save_and_remove_listing():void{$u=$this->user();$l=$this->listing($this->user());$this->actingAs($u)->post(route('properties.favorites.toggle',$l))->assertRedirect();$this->assertDatabaseHas('property_listing_favorites',['user_id'=>$u->id,'property_listing_id'=>$l->id]);$this->actingAs($u)->post(route('properties.favorites.toggle',$l))->assertRedirect();$this->assertDatabaseMissing('property_listing_favorites',['user_id'=>$u->id,'property_listing_id'=>$l->id]);}
public function test_saved_list_is_private_and_only_shows_published():void{$u=$this->user();$a=$this->listing($this->user());$p=$this->listing($this->user(),'pending');$u->favoritePropertyListings()->attach([$a->id,$p->id]);$this->actingAs($u)->get(route('properties.favorites.index'))->assertOk()->assertSee($a->title)->assertDontSee($p->title);$this->actingAs($this->user())->get(route('properties.favorites.index'))->assertOk()->assertDontSee($a->title);}
public function test_guest_cannot_save_listing():void{$this->post(route('properties.favorites.toggle',$this->listing($this->user())))->assertRedirect(route('login'));}
private function user():User{return User::create(['mobile'=>'09'.str_pad((string)random_int(0,999999999),9,'0',STR_PAD_LEFT),'first_name'=>'کاربر','last_name'=>'آزمایشی']);}
private function listing(User $u,string $status='active'):PropertyListing{return PropertyListing::create(['user_id'=>$u->id,'listing_type'=>'sale','property_type'=>'residential','title'=>'آگهی '.uniqid(),'description'=>'توضیحات','city'=>'تهران','status'=>$status]);}}
