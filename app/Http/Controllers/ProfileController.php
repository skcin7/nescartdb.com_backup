<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;

class ProfileController extends Controller
{
    public function cart(Request $request, string|null $cart_id = null, string|null $cart_url_slug = null)
    {
        $cart = Models\Cart::query()
            ->where('cart_id', (string)$cart_id)
            ->with(['submitterUser'])
            ->first();

//        dd( $cart->submitterUser );

        // Ensures that the cart's URL slug segment of the URL is always present and showing in the URL.
        // If for some reason it's blank, then just quickly redirect to the full URL with the slug included.
        if( $cart && is_null($cart_url_slug) ) {
            return redirect( $cart->publicUrl() );
        }


        $unique_game = Models\UniqueGame::query()
            ->where( 'id', $cart->getAttribute('unique_game_id') )
            ->with(['cartProfiles'])
            ->first();

//        dd( $unique_game->cartProfiles->toArray() );

        $cart_profiles_grouped_by_region = [];

        foreach( $unique_game->cartProfiles->toArray() as $cart_profile ) {
//            dd($cart_profile["region"]);
            if( !isset($cart_profiles_grouped_by_region[$cart_profile['region']]) ) {
                $cart_profiles_grouped_by_region[$cart_profile['region']] = [];
            }
            $cart_profiles_grouped_by_region[$cart_profile['region']][] = $cart_profile;
        }

//        dd($cart_profiles_grouped_by_region);


        $more_profiles = Models\Cart::query()
            ->where( 'unique_game_id', $cart->getAttribute('unique_game_id') )
            ->where( 'region', $cart->getAttribute('region') )
            ->get()
//            ->toArray()
        ;
//        dd($more_profiles);

        $related_profiles = Models\Cart::query()
            ->where( 'unique_game_id', $cart->getAttribute('unique_game_id') )
            ->where( 'region', '!=', $cart->getAttribute('region') )
            ->groupBy('region')
            ->get()
//            ->toArray()
        ;
//        dd($related_profiles);

        return view('cart')
            ->with('cart', $cart)
            ->with('more_profiles', $more_profiles)
            ->with('related_profiles', $related_profiles)
//            ->with('unique_game', $unique_game)
            ->with('page_title', $cart->getAttribute('game_title') . " - NesCartDB");
    }

    public function image(Request $request, string|null $cart_id = null)
    {
        $cart = Models\Cart::query()
            ->where('cart_id', (string)$cart_id)
            ->first();

        if( !$cart ) {
            return redirect()->route('welcome');
        }

        // HTTP GET "position" possible values:
        // cart_top
        // cart_front
        // cart_back
        // pcb_front
        // pcb_back
        // box_front
        // box_back
        // manual

//        $cart_images_array = $cart->getAttribute('images');
//        $img_src = $cart_images_array[ $request->get('position') ]



        $img_src = $cart->imageUrl( $request->get('position') );

        $img_title = str_replace("_", " ", $request->get('position'));
        $img_title = ucwords($img_title);

        return view('image')
            ->with( 'img_src', $img_src )
            ->with( 'img_title', $img_title )
            ->with('page_title', "NesCartDB - Image")
            ;
    }
}
