<?php

namespace App\Http\Controllers;
use App\Models\Post;
use App\Models\User;
use App\Models\Image;
use Illuminate\Http\Request;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Notifications\PostNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function search_post($title)
    {
        $search_post = Post::where("title", "like", "%" . $title . "%")->with('user', 'images')->latest()->get();

        $search_post->transform(function ($post) {
            if ($post->user->profile_img) {

                $post->user->profile_img = url(str_replace('public/images/', '/storage/images/', $post->user->profile_img));



            } else {
                $post->user->profile_img = null;
            }

            $post->images->transform(function ($image) {
                if($image->images_post){
                    $image->images_post = url(Storage::url($image->images_post));
                }else{
                    $image->images_post=null;
                }

                return $image;
            });

            return $post;
        });

        return response([
            'data' => $search_post
        ], 200);
    }


    public function get_Post()
    {
        $posts = Post::with('user', 'images')->latest()->get();

        $posts->transform(function ($post) {
            if ($post->user->profile_img) {

                $post->user->profile_img = url(str_replace('public/images/', '/storage/images/', $post->user->profile_img));



            } else {
                $post->user->profile_img = null;
            }

            $post->images->transform(function ($image) {
                if($image->images_post){
                    $image->images_post = url(Storage::url($image->images_post));
                }else{
                    $image->images_post=null;
                }

                return $image;
            });

            return $post;
        });

        return response([
            'post' => $posts
        ], 200);
    }
    public function getPostAsVistor()
    {
        $posts = Post::with('user', 'images')->latest()->get();

        $posts->transform(function ($post) {
            if ($post->user->profile_img) {

                $post->user->profile_img = url(str_replace('public/images/', '/storage/images/', $post->user->profile_img));



            } else {
                $post->user->profile_img = null;
            }

            $post->images->transform(function ($image) {
                if($image->images_post){
                    $image->images_post = url(Storage::url($image->images_post));
                }else{
                    $image->images_post=null;
                }

                return $image;
            });

            return $post;
        });

        return response([
            'post' => $posts
        ], 200);
    }



    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        $notification->markAsRead();

        return response([
            'message' => 'Notification marked as read.'
        ],200);
    }




    public function store(PostRequest $request)
    {
       $request->validated();
        $post = auth()->user()->posts()->create([
            'title' => $request->title,
            'description' => $request->description,
        ]);
        if ($request->hasFile('images_post'))
        {
            foreach ($request->file('images_post') as $images) {
                $path = $images->store('public/posts');
                $post->images()->create([
                    'user_id'=>auth()->user()->id,
                    'images_post'=>$path
                ]);
            }
        }


        $post->user->notify(new PostNotification($post));
        $post->load('user','images');
        return response([
            'message'=>'post created successfuly',
            'data'=>$post,
        ], 200);
    }

    public function update_post(Request $request, $id)
    {
        $post = Post::find($id);

        if ($post) {
            if ($post->user_id == $request->user()->id) {
                $validator = Validator::make($request->all(), [
                    'title' => 'nullable|string',
                    'description' => 'nullable',
                    'images_post.*' => 'nullable|image|mimes:png,jpg'
                ]);

                if ($validator->fails()) {
                    return response([
                        'message' => 'Validation error',
                        'error' => $validator->errors()
                    ], 400);
                }

                $post->update([
                    'title' => $request->title,
                    'description' => $request->description,
                ]);

                if ($request->hasFile('images_post')) {
                    foreach ($post->images as $image) {
                        Storage::delete($image->images_post);
                        $image->delete();
                    }

                    foreach ($request->file('images_post') as $image) {
                        $path = $image->store('public/posts');
                        $image = new Image([
                            'images_post' => $path,
                            'user_id' => auth()->id(),
                        ]);
                        $post->images()->save($image);
                    }
                }

                $post->load('images');
                return response([
                    'message' => 'Post updated successfully',
                    'data' => $post,
                ], 200);
            } else {
                return response([
                    'message' => 'Unauthorized'
                ], 202);
            }
        }

        return response([
            'message' => 'Post not found',
        ], 201);
    }






    public function delete_post(Request $request,$id)
    {
         $post=Post::find($id);
             if ($post)
            {
                if($post->user_id == $request->user()->id)
                {

                    foreach ($post->images as $image) {
                        Storage::delete($image->images_post);

                    }
                     $post->delete();
                     return response([
                     'message'=>'post deleted successfuly',
                     'post'=>$post
                        ], 200);
                } else{
                    return response([
                        'message' => 'Unauthorized'
                     ], 202);
                }
            }

        return response([
             'message'=>'id post not found'
            ],201);


    }


}


?>
