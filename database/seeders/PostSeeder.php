<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->count(1000)->create()->each(function ($user) {
          
            $posts = \App\Models\Post::factory()->count(1000)->make();
            $user->posts()->saveMany($posts);
        });
 
    }
}
