<?php

namespace App\Http\Controllers;

use App\Category;
use App\Directory;
use App\Group;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }
    // ajax call for category from group id.
    public function getCategory(Request $request)
    {
        $categories = Category::where('group_id', $request->group_id)->get();
        return response()->json($categories);
    }
    // adding listing from user
    public function addListing(Request $request)
    {
        $admin_email = User::where('is_admin', 1)->get();
        // dd($admin_email[0]->email);
        $request->validate([
            // 'profile_image' => 'required',
            'profile_image' => 'image|mimes:jpeg,png,jpg,gif,svg',
        ]);
        if ($request->hasfile('profile_image')) {
            $file_name = Str::random(5) . '.' . $request->file('profile_image')->extension();
            $request->file('profile_image')->move(public_path() . '/profile_image/', $file_name);
        }
        $dir = new Directory();
        $dir->name = $request->name;
        $dir->p_img = $file_name;
        $dir->email = $request->email;
        $dir->phone = $request->phone;
        $dir->address = $request->mailing;
        $dir->undergraduate_year = $request->graduation;
        $dir->law_year = $request->law;
        $dir->bar_year = $request->bar;
        $dir->practice_area = $request->areas;
        $dir->category_id = $request->category_name;
        // $dir->user_id = Auth::user()->id;
        $dir->save();
        if (!User::where('email', '=', $request->email)->exists()) {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();
        }
        $details = [
            'title' => 'gatecitybardirectory.org',
            'email' => $request->email,
            'body' => 'This is a notification email from a submission.',
        ];
        $admin_email = trim($admin_email[0]->email);
        \Mail::to($admin_email)->send(new \App\Mail\NotificationEmail($details));
        // dd("Email is Sent.");
        return back()->with('success', 'Thank You for your submission. Your submission is now going through the approval process.');
    }
//    management listings
    public function viewCategory()
    {
        $groups = Group::all();
        return view('customer.category', compact('groups'));
    }
    public function userAddGroup(Request $request)
    {
        Group::create([
            'group_name' => $request->g_name,
        ]);
        return back()->with('success', 'Added the group name successfully');
    }
    public function userDeleteGroup($g_id)
    {
        Group::where('id', $g_id)->delete();
        return back();
    }
    public function userAddCategory($g_id)
    {
        $ctgs = Category::where('group_id', $g_id)->get();
        return view('customer.add_category', compact('g_id', 'ctgs'));
    }
    public function userStoreCategory(Request $request)
    {
        $ctg = new Category();
        $ctg->ctg_name = $request->ctg_name;
        $ctg->group_id = $request->g_id;
        $ctg->save();
        return back();
    }
    public function userDeleteCategory($ctg_id)
    {
        Category::where('id', $ctg_id)->delete();
        return back();
    }

    public function viewListings()
    {
        $listings = Directory::join('categories', 'categories.id', 'directories.category_id')
            ->join('groups', 'groups.id', 'categories.group_id')->get(['categories.*', 'groups.*', 'directories.*']);
        $listings = $listings->groupBy('group_name');
//        dd($listings);
        return view('customer.listings', compact('listings'));
    }
    public function viewEdit($id)
    {
        $groups = Group::all();
        $listing = Directory::where('id', $id)->get();
        return view('customer.edit_listing', compact('groups', 'listing'));
    }
    public function updateListing(Request $request)
    {
        $data = $request->all();
        Directory::where('id', $data['directory_id'])->update(['name' => $data['name'], 'email' => $data['email'],
            'phone' => $data['phone'], 'address' => $data['mailing'], 'undergraduate_year' => $data['graduation'],
            'law_year' => $data['law'], 'bar_year' => $data['bar'], 'practice_area' => $data['areas'], 'category_id' => $data['category_name']]);
        return redirect()->route('view.listings');
    }
    public function deleteListings($id)
    {
        Directory::where('id', $id)->delete();
        return back();
    }
//    pending listings
    public function pendingListings()
    {
        $listings = Directory::join('categories', 'categories.id', 'directories.category_id')
            ->join('groups', 'groups.id', 'categories.group_id')->where('status', 0)->get(['categories.*', 'groups.*', 'directories.*']);
        $listings = $listings->groupBy('group_name');
        return view('customer.pending', compact('listings'));
    }
    public function approveListing($listing_id)
    {
        Directory::where('id', $listing_id)->update(['status' => 1]);
        return back();
    }
}