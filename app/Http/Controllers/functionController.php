<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use Hash;
use DB;
use App\User;
use App\App;
use Input;
use Validator;
use Redirect;
use Session;

class functionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        // GET ALL POST DATA
            $data = Input::all();

        // APPLY VALIDATION RULES
            $rules = array(
                'username' => 'required',
                'password' => 'required|min:5',
            );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()){
            // IF VALIDATION FAILS REDIRECT BACK TO LOGIN
                return Redirect::to('/login')->withInput(Input::except('password'))->withErrors($validator);
        }
        else {
            $userdata = array(
                'user_name' => Input::get('username'),
                'password' => Input::get('password')
            );

            // LOGIN CHECK
                if (Auth::validate($userdata)) {
                    if (Auth::attempt($userdata)) 
                        return Redirect::to('/'.Auth::user()->system_name);
                } 
                else {
                    // IF FAILS SEND BACK WITH A GENERIC ERROR
                    Session::flash('error', 'Username / Password is incorrect.'); 
                    return Redirect::to('/login')->withInput(Input::except('password'));
                }
        }
    }

    public function logout()
    {
        Auth::logout(); // logout user

        return Redirect::to('/login'); //redirect back to login
    }

    public function account_add()
    {
        // GET ALL POST DATA
            $data = Input::all();

        // APPLY VALIDATION RULES
            $rules = array(
                'name' => 'required',
                'description' => 'required',
            );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()){
            // IF VALIDATION FAILS REDIRECT BACK TO LOGIN
                return Redirect::route('add-account',['systemName'=>Auth::user()->system_name])->withInput()->withErrors($validator);
        }
        else {
            Account::create(array(
                'name' => Input::get('name'),
                'description' => Input::get('description'),
                'status' => 'active'
            ));

            Session::flash('success', 'Account Added'); 
            return Redirect::route('accounts',['systemName'=>Auth::user()->system_name]);            
        }        
    }

    public function remove_app($systemName,$appSystemId)
    {
        App::where('system_id',$appSystemId)->delete();

        Session::flash('success', 'App has been removed.'); 
        return Redirect::route('dashboard',['systemName'=>Auth::user()->system_name]);         
    }

    public function createUser()
    {
        User::create(array(
            'first_name' => 'armand',
            'last_name' => 'Janse van Rensburg',
            'user_name' => 'admin',
            'system_name' => '@admin',
            'email'    => 'arrie.code@gmail.com',
            'password' => Hash::make('admin'),
            'status' => 'active'
        ));
    }
}
