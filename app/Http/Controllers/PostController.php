<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;


class PostController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index(){

        $posts = Post::paginate(10000);
        // $users = User::with(['posts'])->get();

        return response()->json(['data'=>$posts]);

    }
    //
}
