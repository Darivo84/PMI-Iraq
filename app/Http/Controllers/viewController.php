<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\App;

class viewController extends Controller
{
    // APP HOME
        public function home(){

            return view('pages.home');
        } 

    // APP HOME
        public function user_add(){

            return view('pages.add');
        }   
        
}
