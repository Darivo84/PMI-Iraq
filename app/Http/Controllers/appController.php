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
use Response;
use DateTime;
use Storage;
use Carbon\Carbon;
use App\Redemption;

class appController extends Controller
{
    /*
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */

    public function app(){
        // TYPES
        // - Login
        // - Logout
        // - Update
        // - Remove
        // - Add
        // - Email

        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata);  
        @$type = $request->type;
        @$data = $request->data; 
        @$app_version = $request->app_version;
        @$datetime = $request->datetime;

        switch ($type) {
            case 'login':
                return $this->login($data->login_id,$data->password,$datetime,$app_version);
                break;
            case 'synch':
                return $this->synch($data->login_id,$data->questions,$data->outlet_stats,$data->redemptions,$datetime,$app_version);
                break; 
            case 'sync':
                return $this->synch($data->login_id,$data->questions,$data->outlet_stats,$data->redemptions,$datetime,$app_version);
                break;                                
         }     
    }

    // MANAGE LOGIN OF USERS
        public function login($login_id,$password,$datetime,$app_version)
        {
            $user = DB::table('users')
                    ->where('login_id',$login_id)
                    ->where('password',$password)
                    ->select('id','first_name','last_name','login_id')
                    ->whereNull('deleted_at')
                    ->first();

            if($user){
                    $date = date('Y-m-d h:i:s');

                    $this->add_request('login',$user->id,'No Data',$app_version,$datetime);

                    DB::table('users')
                        ->where('login_id',$login_id)
                        ->update([
                            "last_login"=>$datetime,
                            'updated_at'=>$date
                        ]);

                return Response::json($user);
            }
            else
                return 'failed';
        }  

    // MANAGE DATA SYNCH
        public function synch($login_id,$questions,$outlet_stats,$redemptions,$datetime,$app_version){
                $newData = Array();
                $date =  Date('Y-m-d');
                $time =  time();
                $SERVER_TIME = date("Y-m-d H:i:s");

                // function base64_to_jpeg($base64_string, $output_file) {
                    function base64_to_jpeg($base64_string, $output_file, $outlet_id, $type) {
                    // $ifp = fopen(base_path() . '/public/images/'.$output_file, "wb"); 

                    // $data = explode(',', $base64_string);

                    // fwrite($ifp, base64_decode($data[1])); 
                    // fclose($ifp); 

                    // return $output_file; 

                    //New logic sending directly to S3
                    
                   // $ifp = fopen('/tmp/'.$output_file, "wb");
                    $ifp = fopen('/tmp/'.$output_file, "wb");

                        $data = explode(',', $base64_string);

                    fwrite($ifp, base64_decode($data[1])); 
                    fclose($ifp); 

                    $remote_image_path = $type.'/'.$outlet_id.'/';

                    Storage::disk('itap_image_store')->put($remote_image_path.$output_file, fopen('/tmp/'.$output_file, 'r+'),'public');
                    unlink('/tmp/'.$output_file);

                    return $output_file; 
                    
                }                
                
                // GET THE USER ID
                    $userID = DB::table('users')->where('login_id',$login_id)->pluck('id'); 

                // ADD QUESTIONS
                    if($questions != 'No Data'){
                        foreach ($questions as $key => $value) {
                           $findQ =  DB::table('questions')
                                    ->where('outlet_id',$value->outlet_id)
                                    ->where('month',$value->month)
                                    ->where('year',$value->year)
                                    ->whereNull('deleted_at')
                                    ->pluck('id');
                           $findOS =  DB::table('outlet_stats')
                                    ->where('outlet_id',$value->outlet_id)
                                    ->where('month',$value->month)
                                    ->where('year',$value->year)
                                    ->whereNull('deleted_at')
                                    ->first();                                    
                           $findQuestion =  DB::table('questions')
                                    ->where('outlet_id',$value->outlet_id)
                                    ->where('month',$value->month)
                                    ->where('year',$value->year)
                                    ->whereNull('deleted_at')
                                    ->first();

                            if($findQ == '')
                            {
                                $UPDATE_ARRAY = [
                                    "outlet_id"  => $value->outlet_id,                                      
                                    "month" => $value->month,   
                                    "year" => $value->year,   
                                    "updated_at" => $SERVER_TIME
                                ];

                                if($value->v1pic != 'empty' && $value->v1pic != null){
                                    $tstStr = explode(',',$value->v1pic);
                                    $tstStr = substr($tstStr[0],0,4);

                                    if($tstStr == 'data'){
                                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_1.jpg';
                                        // $image1 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v1pic, $name );
                                        $image1 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v1pic, $name, $value->outlet_id, 'photo' );
                                    }else{
                                        $image1 = $value->v1pic;
                                    }
                                }else{
                                    $image1 = 'empty';
                                }
                                $UPDATE_ARRAY['v1pic'] = $image1;

                                if($value->v2pic != 'empty' && $value->v2pic != null){
                                    $tstStr = explode(',',$value->v2pic);
                                    $tstStr = substr($tstStr[0],0,4);

                                    if($tstStr == 'data'){
                                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_2.jpg';
                                        // $image2 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v2pic, $name );   
                                        // $image2 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v2pic, $name, $value->outlet_id, 'photo' );
                                        $image2 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v2pic, $name, $value->outlet_id, 'photo' );
                                    }else{
                                        $image2 = $value->v2pic;
                                    }                        
                                }else{
                                    $image2 = 'empty';
                                }
                                $UPDATE_ARRAY['v2pic'] = $image2;

                                if($value->v3pic != 'empty'  && $value->v3pic != null){
                                    $tstStr = explode(',',$value->v3pic);
                                    $tstStr = substr($tstStr[0],0,4);

                                    if($tstStr == 'data'){
                                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_3.jpg';
                                        // $image3 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v3pic, $name );
                                        // $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                                        $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                                    }else{
                                        $image3 = $value->v3pic;
                                    }                    
                                }else{
                                    $image3 = 'empty';
                                }
                                $UPDATE_ARRAY['v3pic'] = $image3;

                                if($value->v4pic != 'empty'  && $value->v4pic != null){
                                    $tstStr = explode(',',$value->v4pic);
                                    $tstStr = substr($tstStr[0],0,4);

                                    if($tstStr == 'data'){
                                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_4.jpg';
                                        // $image4 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v4pic, $name );
                                        // $image4 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v4pic, $name, $value->outlet_id, 'photo' );
                                        $image4 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v4pic, $name, $value->outlet_id, 'photo' );
                                    }else{
                                        $image4 = $value->v4pic;
                                    }                          
                                }else{
                                    $image4 = 'empty';
                                }
                                $UPDATE_ARRAY['v4pic'] = $image4;   

                                if($value->eawnser != 'empty'){
                                        $UPDATE_ARRAY['eawnser'] = $value->eawnser;
                                        $UPDATE_ARRAY['eduservertime'] = $SERVER_TIME;
                                        if(isset($value->edulocaltime))
                                            $UPDATE_ARRAY['edulocaltime'] = $value->edulocaltime;
                                }

                                if($value->vv1a1 != 'empty' || $value->vv1a2 != 'empty'){
                                        $UPDATE_ARRAY['vv1a1'] = $value->vv1a1;
                                        $UPDATE_ARRAY['vv1p1'] = $value->vv1p1;
                                        $UPDATE_ARRAY['vv1a2'] = $value->vv1a2;
                                        $UPDATE_ARRAY['vv1p2'] = $value->vv1p2;
                                        $UPDATE_ARRAY['vv1servertime'] = $SERVER_TIME;
                                        if(isset($value->vv1localtime))
                                            $UPDATE_ARRAY['vv1localtime'] = $value->vv1localtime;
                                }
                                if($value->vv2a1 != 'empty' || $value->vv2a2 != 'empty'){
                                        $UPDATE_ARRAY['vv2a1'] = $value->vv2a1;
                                        $UPDATE_ARRAY['vv2p1'] = $value->vv2p1;
                                        $UPDATE_ARRAY['vv2a2'] = $value->vv2a2;
                                        $UPDATE_ARRAY['vv2p2'] = $value->vv2p2;
                                        $UPDATE_ARRAY['vv2servertime'] = $SERVER_TIME;
                                        if(isset($value->vv2localtime))
                                            $UPDATE_ARRAY['vv2localtime'] = $value->vv2localtime;
                                }
                                if($value->vv3a1 != 'empty' || $value->vv3a2 != 'empty'){
                                        $UPDATE_ARRAY['vv3a1'] = $value->vv3a1;
                                        $UPDATE_ARRAY['vv3p1'] = $value->vv3p1;
                                        $UPDATE_ARRAY['vv3a2'] = $value->vv3a2;
                                        $UPDATE_ARRAY['vv3p2'] = $value->vv3p2;
                                        $UPDATE_ARRAY['vv3servertime'] = $SERVER_TIME;
                                        if(isset($value->vv3localtime))
                                            $UPDATE_ARRAY['vv3localtime'] = $value->vv3localtime;
                                }
                                if($value->vv4a1 != 'empty' || $value->vv4a2 != 'empty'){
                                        $UPDATE_ARRAY['vv4a1'] = $value->vv4a1;
                                        $UPDATE_ARRAY['vv4p1'] = $value->vv4p1;
                                        $UPDATE_ARRAY['vv4a2'] = $value->vv4a2;
                                        $UPDATE_ARRAY['vv4p2'] = $value->vv4p2;
                                        $UPDATE_ARRAY['vv4servertime'] = $SERVER_TIME;
                                        if(isset($value->vv4localtime))
                                            $UPDATE_ARRAY['vv4localtime'] = $value->vv4localtime;
                                }                                                                

                                if($value->av1a != 'empty'){
                                        $UPDATE_ARRAY['av1a'] = $value->av1a;
                                        $UPDATE_ARRAY['av1p'] = $value->av1p;
                                        $UPDATE_ARRAY['av1servertime'] = $SERVER_TIME;
                                        if(isset($value->av1localtime))
                                            $UPDATE_ARRAY['av1localtime'] = $value->av1localtime;
                                }
                                if($value->av2a != 'empty'){
                                        $UPDATE_ARRAY['av2a'] = $value->av2a;
                                        $UPDATE_ARRAY['av2p'] = $value->av2p;
                                        $UPDATE_ARRAY['av2servertime'] = $SERVER_TIME;
                                        if(isset($value->av2localtime))
                                            $UPDATE_ARRAY['av2localtime'] = $value->av2localtime;
                                }
                                if($value->av3a != 'empty'){
                                        $UPDATE_ARRAY['av3a'] = $value->av3a;
                                        $UPDATE_ARRAY['av3p'] = $value->av3p;
                                        $UPDATE_ARRAY['av3servertime'] = $SERVER_TIME;
                                        if(isset($value->av3localtime))
                                            $UPDATE_ARRAY['av3localtime'] = $value->av3localtime;
                                }
                                if($value->av4a != 'empty'){
                                        $UPDATE_ARRAY['av4a'] = $value->av4a;
                                        $UPDATE_ARRAY['av4p'] = $value->av4p;
                                        $UPDATE_ARRAY['av4servertime'] = $SERVER_TIME;
                                        if(isset($value->av4localtime))
                                            $UPDATE_ARRAY['av4localtime'] = $value->av4localtime;
                                }

                                if($value->cea != 'empty'){
                                        $UPDATE_ARRAY['cea'] = $value->cea;
                                        $UPDATE_ARRAY['cep'] = $value->cep;
                                        $UPDATE_ARRAY['ceservertime'] = $SERVER_TIME;
                                        if(isset($value->celocaltime))
                                            $UPDATE_ARRAY['celocaltime'] = $value->celocaltime;
                                }
                                if($value->msv1a != 'empty'){
                                        $UPDATE_ARRAY['msv1a'] = $value->msv1a;
                                        $UPDATE_ARRAY['msv1p'] = $value->msv1p;
                                        $UPDATE_ARRAY['msv1servertime'] = $SERVER_TIME;
                                        if(isset($value->msv1localtime))
                                            $UPDATE_ARRAY['msv1localtime'] = $value->msv1localtime;
                                }
                                if($value->msv2a != 'empty'){
                                        $UPDATE_ARRAY['msv2a'] = $value->msv2a;
                                        $UPDATE_ARRAY['msv2p'] = $value->msv2p;
                                        $UPDATE_ARRAY['msv2servertime'] = $SERVER_TIME;
                                        if(isset($value->msv2localtime))
                                            $UPDATE_ARRAY['msv2localtime'] = $value->msv2localtime;
                                }
                                if($value->rapa != 'empty'){
                                        $UPDATE_ARRAY['rapa'] = $value->rapa;
                                        $UPDATE_ARRAY['rapp'] = $value->rapp;
                                        $UPDATE_ARRAY['rapservertime'] = $SERVER_TIME;
                                        if(isset($value->raplocaltime))
                                            $UPDATE_ARRAY['raplocaltime'] = $value->raplocaltime;
                                }   
                                if($value->npla != 'empty'){
                                        $UPDATE_ARRAY['npla'] = $value->npla;
                                        $UPDATE_ARRAY['nplp'] = $value->nplp;
                                        $UPDATE_ARRAY['nplservertime'] = $SERVER_TIME;
                                        if(isset($value->npllocaltime))
                                            $UPDATE_ARRAY['npllocaltime'] = $value->npllocaltime;
                                }   

                                if($value->sata1 != 'empty'){
                                        $UPDATE_ARRAY['sata1'] = $value->sata1;
                                        $UPDATE_ARRAY['satp1'] = $value->satp1;
                                        $UPDATE_ARRAY['sat1servertime'] = $SERVER_TIME;
                                        if(isset($value->sat1localtime))
                                            $UPDATE_ARRAY['sat1localtime'] = $value->sat1localtime;
                                }                                                                                            
                                if($value->sata2 != 'empty'){
                                        $UPDATE_ARRAY['sata2'] = $value->sata2;
                                        $UPDATE_ARRAY['satp2'] = $value->satp2;
                                        $UPDATE_ARRAY['sat2servertime'] = $SERVER_TIME;
                                        if(isset($value->sat2localtime))
                                            $UPDATE_ARRAY['sat2localtime'] = $value->sat2localtime;
                                } 

                                DB::table('questions')->insert($UPDATE_ARRAY); 
                            }
                            else
                            {
                                if(isset($findOS->completed) && $findOS->completed == '0')
                                {
                                    $UPDATE_ARRAY = [
                                        "outlet_id"  => $value->outlet_id,                                      
                                        "month" => $value->month,   
                                        "year" => $value->year,   
                                        "updated_at" => $SERVER_TIME
                                    ];

                                    if($value->v1pic != 'empty' && $value->v1pic != null){
                                        $tstStr = explode(',',$value->v1pic);
                                        $tstStr = substr($tstStr[0],0,4);

                                        if($tstStr == 'data'){
                                            $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_1.jpg';
                                            // $image1 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v1pic, $name );
                                            // $image1 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v1pic, $name, $value->outlet_id, 'photo' );
                                            $image1 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v1pic, $name, $value->outlet_id, 'photo' );
                                        }else{
                                            $image1 = $value->v1pic;
                                        }
                                    }else{
                                        $image1 = 'empty';
                                    }
                                    $UPDATE_ARRAY['v1pic'] = $image1;

                                    if($value->v2pic != 'empty' && $value->v2pic != null){
                                        $tstStr = explode(',',$value->v2pic);
                                        $tstStr = substr($tstStr[0],0,4);

                                        if($tstStr == 'data'){
                                            $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_2.jpg';
                                            // $image2 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v2pic, $name );  
                                            // $image2 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v2pic, $name, $value->outlet_id, 'photo' ); 
                                            $image2 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v2pic, $name, $value->outlet_id, 'photo' );
                                        }else{
                                            $image2 = $value->v2pic;
                                        }                        
                                    }else{
                                        $image2 = 'empty';
                                    }
                                    $UPDATE_ARRAY['v2pic'] = $image2;

                                    if($value->v3pic != 'empty'  && $value->v3pic != null){
                                        $tstStr = explode(',',$value->v3pic);
                                        $tstStr = substr($tstStr[0],0,4);

                                        if($tstStr == 'data'){
                                            $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_3.jpg';
                                            // $image3 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v3pic, $name );
                                            // $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                                            $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                                        }else{
                                            $image3 = $value->v3pic;
                                        }                    
                                    }else{
                                        $image3 = 'empty';
                                    }
                                    $UPDATE_ARRAY['v3pic'] = $image3;

                                    if($value->v4pic != 'empty'  && $value->v4pic != null){
                                        $tstStr = explode(',',$value->v4pic);
                                        $tstStr = substr($tstStr[0],0,4);

                                        if($tstStr == 'data'){
                                            $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_4.jpg';
                                            // $image4 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v4pic, $name );
                                            // $image4 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v4pic, $name, $value->outlet_id, 'photo' );
                                            $image4 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v4pic, $name, $value->outlet_id, 'photo' );
                                        }else{
                                            $image4 = $value->v4pic;
                                        }                          
                                    }else{
                                        $image4 = 'empty';
                                    }
                                    $UPDATE_ARRAY['v4pic'] = $image4;   

                                    if($value->eawnser != 'empty'){
                                        if(isset($value->edulocaltime)){
                                            if($value->edulocaltime != $findQuestion->edulocaltime){
                                                $UPDATE_ARRAY['eawnser'] = $value->eawnser;
                                                $UPDATE_ARRAY['eduservertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['edulocaltime'] = $value->edulocaltime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['eawnser'] = $value->eawnser;
                                                $UPDATE_ARRAY['eduservertime'] = $SERVER_TIME;                                            
                                        }
                                    }

                                    if($value->vv1a1 != 'empty' || $value->vv1a2 != 'empty'){
                                        if(isset($value->vv1localtime)){
                                            if($value->vv1localtime != $findQuestion->vv1localtime){
                                                $UPDATE_ARRAY['vv1a1'] = $value->vv1a1;
                                                $UPDATE_ARRAY['vv1p1'] = $value->vv1p1;
                                                $UPDATE_ARRAY['vv1a2'] = $value->vv1a2;
                                                $UPDATE_ARRAY['vv1p2'] = $value->vv1p2;
                                                $UPDATE_ARRAY['vv1servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['vv1localtime'] = $value->vv1localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['vv1a1'] = $value->vv1a1;
                                                $UPDATE_ARRAY['vv1p1'] = $value->vv1p1;
                                                $UPDATE_ARRAY['vv1a2'] = $value->vv1a2;
                                                $UPDATE_ARRAY['vv1p2'] = $value->vv1p2;
                                                $UPDATE_ARRAY['vv1servertime'] = $SERVER_TIME;                                            
                                        }
                                    }
                                    if($value->vv2a1 != 'empty' || $value->vv2a2 != 'empty'){
                                        if(isset($value->vv2localtime)){
                                            if($value->vv2localtime != $findQuestion->vv2localtime){
                                                $UPDATE_ARRAY['vv2a1'] = $value->vv2a1;
                                                $UPDATE_ARRAY['vv2p1'] = $value->vv2p1;
                                                $UPDATE_ARRAY['vv2a2'] = $value->vv2a2;
                                                $UPDATE_ARRAY['vv2p2'] = $value->vv2p2;
                                                $UPDATE_ARRAY['vv2servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['vv2localtime'] = $value->vv2localtime;
                                            }
                                        }else{
                                            $UPDATE_ARRAY['vv2a1'] = $value->vv2a1;
                                            $UPDATE_ARRAY['vv2p1'] = $value->vv2p1;
                                            $UPDATE_ARRAY['vv2a2'] = $value->vv2a2;
                                            $UPDATE_ARRAY['vv2p2'] = $value->vv2p2;
                                            $UPDATE_ARRAY['vv2servertime'] = $SERVER_TIME;                                          
                                        }                                        
                                    }
                                    if($value->vv3a1 != 'empty' || $value->vv3a2 != 'empty'){
                                        if(isset($value->vv3localtime)){
                                            if($value->vv3localtime != $findQuestion->vv3localtime){
                                                $UPDATE_ARRAY['vv3a1'] = $value->vv3a1;
                                                $UPDATE_ARRAY['vv3p1'] = $value->vv3p1;
                                                $UPDATE_ARRAY['vv3a2'] = $value->vv3a2;
                                                $UPDATE_ARRAY['vv3p2'] = $value->vv3p2;
                                                $UPDATE_ARRAY['vv3servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['vv3localtime'] = $value->vv3localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['vv3a1'] = $value->vv3a1;
                                                $UPDATE_ARRAY['vv3p1'] = $value->vv3p1;
                                                $UPDATE_ARRAY['vv3a2'] = $value->vv3a2;
                                                $UPDATE_ARRAY['vv3p2'] = $value->vv3p2;
                                                $UPDATE_ARRAY['vv3servertime'] = $SERVER_TIME;                                         
                                        }                                        
                                    }
                                    if($value->vv4a1 != 'empty' || $value->vv4a2 != 'empty'){
                                        if(isset($value->vv4localtime)){
                                            if($value->vv4localtime != $findQuestion->vv4localtime){
                                                $UPDATE_ARRAY['vv4a1'] = $value->vv4a1;
                                                $UPDATE_ARRAY['vv4p1'] = $value->vv4p1;
                                                $UPDATE_ARRAY['vv4a2'] = $value->vv4a2;
                                                $UPDATE_ARRAY['vv4p2'] = $value->vv4p2;
                                                $UPDATE_ARRAY['vv4servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['vv4localtime'] = $value->vv4localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['vv4a1'] = $value->vv4a1;
                                                $UPDATE_ARRAY['vv4p1'] = $value->vv4p1;
                                                $UPDATE_ARRAY['vv4a2'] = $value->vv4a2;
                                                $UPDATE_ARRAY['vv4p2'] = $value->vv4p2;
                                                $UPDATE_ARRAY['vv4servertime'] = $SERVER_TIME;                                        
                                        }                                        
                                    }                                                                

                                    if($value->av1a != 'empty'){
                                        if(isset($value->av1localtime)){
                                            if($value->av1localtime != $findQuestion->av1localtime){
                                                $UPDATE_ARRAY['av1a'] = $value->av1a;
                                                $UPDATE_ARRAY['av1p'] = $value->av1p;
                                                $UPDATE_ARRAY['av1servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['av1localtime'] = $value->av1localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['av1a'] = $value->av1a;
                                                $UPDATE_ARRAY['av1p'] = $value->av1p;
                                                $UPDATE_ARRAY['av1servertime'] = $SERVER_TIME;                                        
                                        }                                         
                                    }
                                    if($value->av2a != 'empty'){
                                        if(isset($value->av2localtime)){
                                            if($value->av2localtime != $findQuestion->av2localtime){
                                                $UPDATE_ARRAY['av2a'] = $value->av2a;
                                                $UPDATE_ARRAY['av2p'] = $value->av2p;
                                                $UPDATE_ARRAY['av2servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['av2localtime'] = $value->av2localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['av2a'] = $value->av2a;
                                                $UPDATE_ARRAY['av2p'] = $value->av2p;
                                                $UPDATE_ARRAY['av2servertime'] = $SERVER_TIME;                                        
                                        }                                         
                                    }
                                    if($value->av3a != 'empty'){
                                        if(isset($value->av3localtime)){
                                            if($value->av3localtime != $findQuestion->av3localtime){
                                                $UPDATE_ARRAY['av3a'] = $value->av3a;
                                                $UPDATE_ARRAY['av3p'] = $value->av3p;
                                                $UPDATE_ARRAY['av3servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['av3localtime'] = $value->av3localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['av3a'] = $value->av3a;
                                                $UPDATE_ARRAY['av3p'] = $value->av3p;
                                                $UPDATE_ARRAY['av3servertime'] = $SERVER_TIME;                                        
                                        }                                          
                                    }
                                    if($value->av4a != 'empty'){
                                        if(isset($value->av4localtime)){
                                            if($value->av4localtime != $findQuestion->av4localtime){
                                                $UPDATE_ARRAY['av4a'] = $value->av4a;
                                                $UPDATE_ARRAY['av4p'] = $value->av4p;
                                                $UPDATE_ARRAY['av4servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['av4localtime'] = $value->av4localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['av4a'] = $value->av4a;
                                                $UPDATE_ARRAY['av4p'] = $value->av4p;
                                                $UPDATE_ARRAY['av4servertime'] = $SERVER_TIME;                                       
                                        }                                         
                                    }

                                    if($value->cea != 'empty'){
                                        if(isset($value->celocaltime)){
                                            if($value->celocaltime != $findQuestion->celocaltime){
                                                $UPDATE_ARRAY['cea'] = $value->cea;
                                                $UPDATE_ARRAY['cep'] = $value->cep;
                                                $UPDATE_ARRAY['ceservertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['celocaltime'] = $value->celocaltime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['cea'] = $value->cea;
                                                $UPDATE_ARRAY['cep'] = $value->cep;
                                                $UPDATE_ARRAY['ceservertime'] = $SERVER_TIME;                                    
                                        }                                        
                                    }
                                    if($value->msv1a != 'empty'){
                                        if(isset($value->msv1localtime)){
                                            if($value->msv1localtime != $findQuestion->msv1localtime){
                                                $UPDATE_ARRAY['msv1a'] = $value->msv1a;
                                                $UPDATE_ARRAY['msv1p'] = $value->msv1p;
                                                $UPDATE_ARRAY['msv1servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['msv1localtime'] = $value->msv1localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['msv1a'] = $value->msv1a;
                                                $UPDATE_ARRAY['msv1p'] = $value->msv1p;
                                                $UPDATE_ARRAY['msv1servertime'] = $SERVER_TIME;                                    
                                        }                                        
                                    }
                                    if($value->msv2a != 'empty'){
                                        if(isset($value->msv2localtime)){
                                            if($value->msv2localtime != $findQuestion->msv2localtime){
                                                $UPDATE_ARRAY['msv2a'] = $value->msv2a;
                                                $UPDATE_ARRAY['msv2p'] = $value->msv2p;
                                                $UPDATE_ARRAY['msv2servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['msv2localtime'] = $value->msv2localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['msv2a'] = $value->msv2a;
                                                $UPDATE_ARRAY['msv2p'] = $value->msv2p;
                                                $UPDATE_ARRAY['msv2servertime'] = $SERVER_TIME;                                  
                                        }                                         
                                    }
                                    if($value->rapa != 'empty'){
                                        if(isset($value->raplocaltime)){
                                            if($value->raplocaltime != $findQuestion->raplocaltime){
                                                $UPDATE_ARRAY['rapa'] = $value->rapa;
                                                $UPDATE_ARRAY['rapp'] = $value->rapp;
                                                $UPDATE_ARRAY['rapservertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['raplocaltime'] = $value->raplocaltime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['rapa'] = $value->rapa;
                                                $UPDATE_ARRAY['rapp'] = $value->rapp;
                                                $UPDATE_ARRAY['rapservertime'] = $SERVER_TIME;                                 
                                        }                                        
                                    }   
                                    if($value->npla != 'empty'){
                                        if(isset($value->npllocaltime)){
                                            if($value->npllocaltime != $findQuestion->npllocaltime){
                                                $UPDATE_ARRAY['npla'] = $value->npla;
                                                $UPDATE_ARRAY['nplp'] = $value->nplp;
                                                $UPDATE_ARRAY['nplservertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['npllocaltime'] = $value->npllocaltime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['npla'] = $value->npla;
                                                $UPDATE_ARRAY['nplp'] = $value->nplp;
                                                $UPDATE_ARRAY['nplservertime'] = $SERVER_TIME;                               
                                        }                                         
                                    }   

                                    if($value->sata1 != 'empty'){
                                        if(isset($value->sat1localtime)){
                                            if($value->sat1localtime != $findQuestion->sat1localtime){
                                                $UPDATE_ARRAY['sata1'] = $value->sata1;
                                                $UPDATE_ARRAY['satp1'] = $value->satp1;
                                                $UPDATE_ARRAY['sat1servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['sat1localtime'] = $value->sat1localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['sata1'] = $value->sata1;
                                                $UPDATE_ARRAY['satp1'] = $value->satp1;
                                                $UPDATE_ARRAY['sat1servertime'] = $SERVER_TIME;                              
                                        }                                        
                                    }                                                                                            
                                    if($value->sata2 != 'empty'){
                                        if(isset($value->sat1localtime)){
                                            if($value->sat2localtime != $findQuestion->sat2localtime){
                                                $UPDATE_ARRAY['sata2'] = $value->sata2;
                                                $UPDATE_ARRAY['satp2'] = $value->satp2;
                                                $UPDATE_ARRAY['sat2servertime'] = $SERVER_TIME;
                                                $UPDATE_ARRAY['sat2localtime'] = $value->sat2localtime;
                                            }
                                        }else{
                                                $UPDATE_ARRAY['sata2'] = $value->sata2;
                                                $UPDATE_ARRAY['satp2'] = $value->satp2;
                                                $UPDATE_ARRAY['sat2servertime'] = $SERVER_TIME;                             
                                        }                                         
                                    } 

                                    DB::table('questions')->where('id',$findQ)->update($UPDATE_ARRAY);                                   
                                }
                            }
                        } 
                    }                  
                    
                // ADD THE OUTLET STATS
                    if($outlet_stats != 'No Data'){
                        foreach ($outlet_stats as $key => $value) {
                           $find =  DB::table('outlet_stats')
                                    ->where('outlet_id',$value->outlet_id)
                                    ->where('month',$value->month)
                                    ->where('year',$value->year)
                                    ->whereNull('deleted_at')
                                    ->first();

                            if(count($find) < 1){
                                DB::table('outlet_stats')->insert([
                                    "outlet_id" => $value->outlet_id,
                                    "prize_total" => $value->prize_total,
                                    "foc_total" => $value->foc_total,
                                    "prizes_redeemed" => $value->prizes_redeemed,
                                    "foc_redeemed" => $value->foc_redeemed,
                                    "rank" => $value->rank,
                                    "rap_month" => $value->rap_month,
                                    "npl_month" => $value->npl_month,
                                    "a_month" => $value->a_month,
                                    "v_month" => $value->v_month,
                                    "ms_month" => $value->ms_month,
                                    "ce_month" => $value->ce_month,
                                    "sat_month" => $value->sat_month,
                                    "redeemed_month" => $value->redeemed_month,
                                    "education" => $value->education,
                                    "consumer_engagement" => $value->consumer_engagement,
                                    "mystery_shopper" => $value->mystery_shopper,
                                    "visibility" => $value->visibility,
                                    "availability" => $value->availability,
                                    "rap" => $value->rap,
                                    "npl" => $value->npl,
                                    "sat" => $value->sat,
                                    "points_before" => $value->points_before,
                                    "month" => $value->month,
                                    "year" => $value->year,
                                    "started" => $value->started,
                                    "completed" => $value->completed,
                                    "updated_at" => $value->updated_at
                                ]);
                            }else{
                                if($find->completed == '0'){
                                    DB::table('outlet_stats')->where('id',$find->id)->update([
                                        "outlet_id" => $value->outlet_id,
                                        "prize_total" => $value->prize_total,
                                        "foc_total" => $value->foc_total,
                                        "prizes_redeemed" => $value->prizes_redeemed,
                                        "foc_redeemed" => $value->foc_redeemed,
                                        "rank" => $value->rank,
                                        "rap_month" => $value->rap_month,
                                        "npl_month" => $value->npl_month,
                                        "a_month" => $value->a_month,
                                        "v_month" => $value->v_month,
                                        "ms_month" => $value->ms_month,
                                        "ce_month" => $value->ce_month,
                                        "sat_month" => $value->sat_month,
                                        "redeemed_month" => $value->redeemed_month,
                                        "education" => $value->education,
                                        "consumer_engagement" => $value->consumer_engagement,
                                        "mystery_shopper" => $value->mystery_shopper,
                                        "visibility" => $value->visibility,
                                        "availability" => $value->availability,
                                        "rap" => $value->rap,
                                        "npl" => $value->npl,
                                        "sat" => $value->sat,
                                        "points_before" => $value->points_before,
                                        "month" => $value->month,
                                        "year" => $value->year,
                                        "started" => $value->started,
                                        "completed" => $value->completed,
                                        "updated_at" => $value->updated_at
                                    ]);                         
                                }           
                            }
                        }

                        $outlet_stats_new = json_encode($outlet_stats);
                    }else{
                        $outlet_stats_new = 'No Data';
                    } 

                // ADD REDEMPTIONS
                    $counter = 0;
                    if($redemptions != 'No Data'){ 
                        foreach ($redemptions as $key => $value) {
                            $newtime =  time();
                            $counter++;
                            $findOS =  DB::table('outlet_stats')
                                    ->where('outlet_id',$value->outlet_id)
                                    ->where('month',number_format($value->month))
                                    ->where('year',$value->year)
                                    ->whereNull('deleted_at')
                                    ->first(); 

                            //if($findOS->completed == '0' || $findOS->completed == '1' && $findOS->created_at >= $SERVER_TIME){                        
                                $tstStr = explode(',',$value->signature);
                                $tstStr = substr($tstStr[0],0,4);

                                if($tstStr == 'data'){
                                    $name = $counter.$value->outlet_id.$date.$value->month.$value->year.$newtime.'_signature.png';
                                    // $signature = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->signature, $name );
                                    $signature = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/signature/'.$value->outlet_id.'/'.base64_to_jpeg( $value->signature, $name, $value->outlet_id, 'signature' );
                                }else{
                                    $signature = $value->signature;
                                }
                                
                                $new_redemption = Redemption::firstOrNew(array('outlet_id'=>$value->outlet_id,'year'=>$value->year,'month'=>number_format($value->month),'redemption_datetime'=>$value->updated_at));
                                $new_redemption->name=$value->name;
                                $new_redemption->points=$value->points;
                                $new_redemption->signature=$signature;
                                $new_redemption->save();
                                // DB::table('redemptions')->insert([
                                //     "outlet_id"  => $value->outlet_id,
                                //     "name" => $value->name,
                                //     "points" => $value->points,
                                //     "month" => number_format($value->month),   
                                //     "year" => $value->year,  
                                //     "signature" => $signature 
                                // ]);  
                            //}
                        } 
                    }                     

                    // DELETE DUPLICATES
                    // DELETE n1 FROM redemptions n1, redemptions n2 WHERE n1.id > n2.id AND n1.outlet_id = n2.outlet_id AND n1.name = n2.name AND n1.points = n2.points AND n1.month = n2.month AND n1.year = n2.year AND n1.signature = n2.signature
                    // DELETE n1 FROM redemptions n1, redemptions n2 WHERE n1.id > n2.id AND n1.outlet_id = n2.outlet_id AND n1.name = n2.name AND n1.points = n2.points AND n1.month = n2.month AND n1.year = n2.year AND n1.created_at = n2.created_at AND n1.created_at > '2016-08-17 08:00:00' 
                    $this->add_request('sync',$userID,$outlet_stats_new,$app_version,$datetime);

                // PULL ALL THE OUTLETS  
                    $newData['outlets'] = DB::table('outlets')
                                ->where('user_id',$userID)
                                ->select('id','name_arabic as name','program_id','news_page_code')
                                ->whereNull('deleted_at')
                                ->get();
            

                // PULL ALL THE OUTLET STATS 
                    // GET OUTLET IDS
                    $outletIDS = Array();
                    foreach ($newData['outlets'] as $key => $value) {
                        array_push($outletIDS,$value->id);
                    }

                    //limit results to sync to last 12 months.
                    $limit_year = date("Y", strtotime( date( 'Y-m' )." -11 months"));
                    $limit_month = date("n", strtotime( date( 'Y-m' )." -11 months"));
                    $current_year = date("Y");

                    $newData['outlet_stats'] = DB::table('outlet_stats')
                                ->select('outlet_id','prize_total','foc_total','prizes_redeemed','foc_redeemed','rank','rap_month','npl_month','a_month','v_month','ms_month','ce_month','sat_month','redeemed_month','education','consumer_engagement','mystery_shopper','visibility','availability','rap','npl','sat','points_before','month','year','started','completed','updated_at')
                                ->whereIn('outlet_id',$outletIDS)
                                ->whereNull('deleted_at')
                                ->where('year','>=',$limit_year)
                                ->where('month','>=',$limit_month)
                                ->orWhere('year',$current_year)
                                ->whereIn('outlet_id',$outletIDS)
                                ->whereNull('deleted_at')
                                ->get(); 

                    $newData['questions'] = DB::table('questions')
                                ->whereIn('outlet_id',$outletIDS)
                                ->whereNull('deleted_at')
                                ->where('year','>=',$limit_year)
                                ->where('month','>=',$limit_month)
                                ->orWhere('year',$current_year)
                                ->whereIn('outlet_id',$outletIDS)
                                ->whereNull('deleted_at')
                                ->get();

                // DETERMINE RANK
                    $getstats = DB::select(DB::raw("SELECT t1.*,t4.program_id FROM `outlet_stats` t1 LEFT JOIN `outlets` t4 ON t1.outlet_id = t4.id where UNIX_TIMESTAMP(CONCAT_WS('/',year,month,'01 00:00:00')) = (SELECT MAX(UNIX_TIMESTAMP (CONCAT_WS('/',year,month,'01 00:00:00'))) from outlet_stats t2 LEFT JOIN outlets t3 ON t2.outlet_id = t3.id  where t2.outlet_id = t1.outlet_id)"));

                    $p1 = Array();
                    $p2 = Array();
                    $p3 = Array();

                    foreach ($getstats as $key => $value) {
                        if($value->foc_total == null)$foc_total = 0; else $foc_total = $value->foc_total;
                        if($value->prize_total == null)$prize_total = 0; else $prize_total = $value->prize_total;

                        if($value->program_id == '1'){
                            if($value->started == 1 && $value->completed == 1)
                                $p1[$value->id] = $prize_total + $foc_total;
                        }else if($value->program_id == '2'){
                            if($value->started == 1 && $value->completed == 1)
                              $p2[$value->id] = $prize_total + $foc_total;
                        }else if($value->program_id == '3'){
                            if($value->started == 1 && $value->completed == 1)
                                $p3[$value->id] = $prize_total + $foc_total;
                        }                
                    }

                    arsort($p1);
                    arsort($p2);
                    arsort($p3);

                    $count1 = 0;
                    if(count($p1) > 0)
                    {
                        foreach ($p1 as $key => $value) {
                            $count1++;

                            DB::table('outlet_stats')->where('id',$key)->update([
                                "rank"=>$count1    
                            ]); 
                        } 
                    }   

                    $count2 = 0;
                    if(count($p2) > 0)
                    {
                        foreach ($p2 as $key => $value) {
                            $count2++;

                            DB::table('outlet_stats')->where('id',$key)->update([
                                "rank"=>$count2    
                            ]); 
                        }
                    }       

                    $count3 = 0;
                    if(count($p3) > 0)
                    {
                        foreach ($p3 as $key => $value) {
                            $count3++;

                            DB::table('outlet_stats')->where('id',$key)->update([
                                "rank"=>$count3    
                            ]); 
                        }   
                    }                                                    

                // PULL THE GLOBAL DATA     
                    // GET ALL STATS TO USE 
                    $newData['global_stats'] = DB::select(DB::raw("SELECT t1.program_id,ifNUll(count(t1.id) - 1,0)  as TotalOutlets,ifNUll(floor(sum(ifNUll(t2.consumer_engagement,0)) / ifNUll(count(t1.id) - 1,0)),0) as consumer_engagement,ifNUll(floor(sum(ifNUll(t2.mystery_shopper,0)) / (count(t1.id) - 1)),0) as mystery_shopper,ifNUll(floor(sum(ifNUll(t2.visibility,0)) / (count(t1.id) - 1)),0) as visibility,ifNUll(floor(sum(ifNUll(t2.availability,0)) / (count(t1.id) - 1)),0) as availability,ifNUll(floor(sum(ifNUll(t2.rap,0)) / (count(t1.id) - 1)),0) as rap,ifNUll(floor(sum(ifNUll(t2.npl,0)) / (count(t1.id) - 1)),0) as npl,ifNUll(floor(sum(ifNUll(t2.sat,0)) / ifNUll(count(t1.id) - 1,0)),0) as sales_target FROM outlets t1 left join outlet_stats t2 on t1.id = t2.outlet_id where t2.month = (SELECT MAX(t3.month) from outlet_stats t3 where t2.outlet_id = t3.outlet_id and t3.completed = 1) group by t1.program_id"));
    
                return $newData;
        }

        public function test(){

            // function base64_to_jpeg($base64_string, $output_file) {
                function base64_to_jpeg($base64_string, $output_file, $outlet_id, $type) {
                // $ifp = fopen(base_path() . '/public/images/'.$output_file, "wb"); 

                // $data = explode(',', $base64_string);

                // fwrite($ifp, base64_decode($data[1])); 
                // fclose($ifp); 

                // return $output_file; 

                //New logic sending directly to S3
                    
                    $ifp = fopen('/tmp/'.$output_file, "wb"); 

                    $data = explode(',', $base64_string);

                    fwrite($ifp, base64_decode($data[1])); 
                    fclose($ifp); 

                    $remote_image_path = $type.'/'.$outlet_id.'/';

                    Storage::disk('itap_image_store')->put($remote_image_path.$output_file, fopen('/tmp/'.$output_file, 'r+'),'public');
                    unlink('/tmp/'.$output_file);

                    return $output_file; 
            }

            // $alloutletsStats = DB::select(DB::raw("SELECT t1.*,t4.program_id FROM `outlet_stats` t1 LEFT JOIN `outlets` t4 ON t1.outlet_id = t4.id where UNIX_TIMESTAMP(CONCAT_WS('/',year,month,'01 00:00:00')) = (SELECT MAX(UNIX_TIMESTAMP (CONCAT_WS('/',year,month,'01 00:00:00'))) from outlet_stats t2 LEFT JOIN outlets t3 ON t2.outlet_id = t3.id  where t2.outlet_id = t1.outlet_id)"));
            // $getstats = DB::select(DB::raw("SELECT t1.*,t4.program_id FROM `outlet_stats` t1 LEFT JOIN `outlets` t4 ON t1.outlet_id = t4.id where UNIX_TIMESTAMP(CONCAT_WS('/',year,month,'01 00:00:00')) = (SELECT MAX(UNIX_TIMESTAMP (CONCAT_WS('/',year,month,'01 00:00:00'))) from outlet_stats t2 LEFT JOIN outlets t3 ON t2.outlet_id = t3.id  where t2.outlet_id = t1.outlet_id)"));

            // var_dump($getstats);

            // var_dump($alloutletsStats);
            $question = DB::table('questions')->whereNull('deleted_at')->get();   

            $redemptions = DB::table('redemptions')->whereNull('deleted_at')->select('id','outlet_id','name','points','month','year','signature')->distinct()->get();
            //$str = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAASABIAAD/4QBMRXhpZgAATU0AKgAAAAgAAgESAAMAAAABAAEAAIdpAAQAAAABAAAAJgAAAAAAAqACAAQAAAABAAAAuqADAAQAAAABAAAA+gAAAAD/7QA4UGhvdG9zaG9wIDMuMAA4QklNBAQAAAAAAAA4QklNBCUAAAAAABDUHYzZjwCyBOmACZjs+EJ+/8AAEQgA+gC6AwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/bAEMAAgICAgICBAICBAYEBAQGCAYGBgYICggICAgICgwKCgoKCgoMDAwMDAwMDA4ODg4ODhAQEBAQEhISEhISEhISEv/bAEMBAwMDBQQFCAQECBMNCw0TExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTExMTE//dAAQADP/aAAwDAQACEQMRAD8A+VU1iPd5pIYD+vX/AD7V01nrERjAXAHp9K8AF6cfu24xnnitOy1hosxsxwfQ8USTa0IaZ9C/2nCuA7DK8/8A16zLrWkjJVWAPqa8iTXW+8T+PtUh1QSgYPP1/Kp5XYL9z0uPVkuG+Uj0P+FdFaXoUKO49K8etNQCsGLd+a6S21eGMBs4PovelZjT6o9eg1cKRleOxrSGuwJjOCfWvIE15ySidvetCG/8z/WEc9eaSHuerf2sJAWQYyMHvUZuHlOGAXH6159BqO0YVuBVtdZCpjvjOKSTJutmd2rgfOxwV4pXuxEpCNk4/SvPn1nBIDcZ7Gpo9SjkIVmoa7lJ3Oklu2BJFNW+KDzAMY9axDex4xxzk1WkaQp5g9aNwdjpo9WDtn+dasOqbfmck155Fwxy1abXOE4OaNyYs73+2VxhuQcCpYdcy4JFeafa5lbIPB6VMl4ypwTye1S0xo9ZXWIiuM/e7Uh1RfldWzivOINSKEAnjvitFNQA69eo5qLFtnosF80/yv8ArWhv247Y6fSuEttROASO9bjaqo6t3pWKTudL9uAwme+RVE/YiSStc7PqKH7nbrWSNaYcbW/IVcRNpH//0PzITWCwGPT09etOW9d19ADmuDivcknJxWlBfoBgt7Vq422JaZ3EF95YLsc/zq6mop5Y3ttx61581/j5R609NRY5B7UnHqG56emoFwJSc84q+uoMNu5sCvNYb8PyDnOOlbENzGcbycf4UnAdrHqNnqSsDhsk8Dmugiuw+CeRXlVlfqmGU/hjP411MOqpwqtjHapsFr7nokV0VADEZ96Sa4Cx7QcgnGK4qTVsISpH51JFqm8hSc54NS0Q076HQPcAplep4x9KuWMsgXngf1rmWugSGJq5BqSqpEh5H5Un5Fct9bncRXKpHvz6cetWBfsyZ3f5FcNJqS/fB7dBUb6uRhAam2ocvQ75L1Ap3HJ6fpSSXAKBicD0rhG1VAmCelVjrgbCLx646A0KLvoTY7ttT2BlB7dacb3cMxt17eteepfMzjnFaMdw27fwfWjYux3EN64bLd81eg1GMNuOM49a4bz2JwpzxR9oAOc5xxxU2vsCPSl1BAODirIv3AyGyO1cBBdsBx+ZrVhvDwO3rT5RndRXQYHLfhTvPU8gD/P4VxjX0hIVeRn/ADzTft3u1JoOax//0fxjW9IXjJ+tPF04UDOOlYjSjAC9+1IJWzXQD3OjW8JHLYqRbwgk/hXORyEjg/jVqGcg9uKaBI6eDUJFPJ+ua04dQlZ9obg81yCNleua0YJgpAOPUUpBY762v7gdD/TmtBdQkBHzVxFvdHPHBq8Zy/BOe2aiwm9DtU1SQr8pxWjaalI7cnAHFcRDN0BHsa24biMg54IHak0JHeRalIW+Y9qc92x7gCuTguUJDMcZFWnuVHIOe2Kn0Fe51Ud+wQ7iTxwTVJrvfkA/rXPtqHGKXziwyMZ64/Sp22KOqiui5AbP+NWAQOnNckl4Y2yxz1qQaiW+fdkdDRqD7naJdKq5Zc/hVuG+UNhvSuMF4xQ7zgCkN6sSDbkkcdeKSjcZ6KL2HacE5FTreQJ1OSORn/PWvNY9QcjGKtxTSSEnOB2P6UWBI9HS/BbKnmtS3vIixy2T15rzW2nkVQrHJrUS6KkYOP51FhtHoyTL0jxkDpn3qt5h/wCejD8f/rVydrfF+eenPNXPNlPO4f8AfdKStuGnU//S/DzdngU8DAz+tUQzA5z9KmVuOK6GBZVyOFPHrVqNsf0rOBxhl71MjY6mgaNYSEHk9T+NWFcEnHXtWQHJ5H5VZWTt3NDYr9jegnkUA+la6XKYG7jv+dcpHOFTnvV5JsL7Y6VLE0dMt8qjgnjn8auwXyoMOffNcksgI9OKlWcUmK1jrxqQ5Mbc1PHqEhzk9fSuOE7A1YS7ZRxSSGrnYC6OzrnNWkusRjd2rkorpiduc/1qcXO1MnuealoVjfmv2/hPTtTba/diEc/lXOefubr14GKsW8uTkkCmCO3guNy896ueZgFTz6VyC3WPujHFXPt4IGR9anqOx0sdwQSz/TmtW2vCEwOg9a4s3Y7cemamt7rYA2c4pS1Hax332mMYHXHSnrd4GByOxrkIbws6k89eO9a6P5iqykcd/rQkG+x0cN2yPvJyPWtRbmcgFWjwemSM1yJd4kbfnj1o8+Q8kn86HYei6H//0/woMh24qRZcDIFV+B0oXjkcCt2MtiTPWpEk5wxqqHzzikVwBmgXqaccmQMH61ZVyOax1kI6VYSQ9zQ0BqByeTzU/nkfKKxxKxzg9asb+Bz+VSDNJbkpgn0qZbkHrWNuI5NKGyuaXmB0SXCFc546U/7UM5JrnElIGKf5m0YHNMR0iXnzcdKupPnHPfiuTjmZeBVyO5IxgmkM6WKTdwBwOlXEbB75rnoblWPpjpVz7bGgLE9PwoFqdIkncH8KTz1Hynn1rnUvtwqdLoEAZ7UrD1OhinxnNals+7J7VyUUjM3tWslwUHJxihoLHTrMqJvB/OrtrqjIdr8g8c1xv2skYB98VALxwV5470WHtoepf2hEy7jwAMYqsJc8hxg1w8d84AbJxn86eLmAjPlk575FS0uocx//1Pwe4645qQA9aiwep5pw4BrZjJAe3el6j5aZnvT8gU7iHZI+tPBJ61AWPGDTicnANIC2H49+9PDAcGqu4Babk0gZfEi7aPMzwDVIHnilBP50CRb80EH0NLv6d6rrk08MBxSGWElcVMJSB71U9+maeCaGBeSQipfNY8DpWeJMnBqwrcfhR1FYuRuQQfWriORwp/HvWQG2j36VajkOcmgaN6GZlwAfarsd0d2M1ipNgAt3qwkynk/rQM2/tJdM+nSkWQtwxzzjj+VZfnYAFWBcKScDFFhJdTVQ7T7DpVYzQ5PP6VA0xI3E59aiLnPT+VGgz//V/BzIyM0qn8aiyRTwR2rZjHZycDrS5PeowOeaM46UCJCT1NL1HJqIbs+wp4IAoAfknrTt1RE+lLnHFIGT7gOtAb3qDccYpQw60CLW75aaGFQBsdaUN3NAy1vOM96PM71XyaUHNIC0GAzUqyelUQTnmnBu1MRo+acZp6yEc1QVxwTU6sDz69aBmoG7mrUchrJjdj9KsrIe/WiwGkszY4qRZR2OPWqCPg4PenhgDzTQzUWbnA6e9L5rdlX9f8Kpocj9Kf8AabgcBR+lJrsNW6s//9b8Fs8Uozim9sGl7cGtgYdqXGOKQYxg0ueKQAenFKTimdqXPY9qAHZzwKdn3qP8aU+1AD88cUg9TTTS5pCsPzmlHvSZwM0uRmhDHg+tOB6AVFxTlPHFAEvXkUucfdqMfWng4zmhASAELk1IBleO9RDnipeABnimBOjdyKsKcjBNU92OlPjPOSaBl9G/vVYDVSDYANTLJk4phuaCc9Din7c84P5VTSYrgCnfal7yL+v+FA1bqf/X/BPcaUHNJnigZHWtRsUEZxmlJxTPelPI5oEKDnFOJ5pgOMUvQUAL7Cjp1pM0hNIB3SnCo805TQA/696U0gwaUZHSgBxyRzQvrSgjqaOvFIA708ZIyKYOTT+3WmBICStSBv4agFScYzTAmHTrUg3etQIxFTKMjr1oGTrnrmp1wACCardDnFSKwPHSgVizu5wTin+XIeR/n9agDZ6c0vmAcYovYtH/0PwT7Yo4ximgYNIDWo2P9RSc4o3DFHNAhc80hxTfpSkdjQAo96Dk9O1IKdjFACDHUdKeBkcUwdOaeCMcUgHdBS56UztTgPSmA/JOacODTO1Ln0pAOJ5xSjpTAKXnvTAlBIoJJGaZ060vSgCYdzUitjpUHTk09DzzQBMGywJ6CpfMwOfrVUEjp1qQHOCvGaZWxdDEAE0fMOMH9KgDEH2p/lO3O9efekK1z//R/BH2FGOaQ8Cl+nNagB6e9KR2pBzyaKBh9Kdgik5zSmgQcCgYxQMEUZ4oAO2aUjNAGRS8GkAD2o6YAFKMUA+tMBw6YozSZ47Uc44pAP5pePxpoFO7cUwHfWndiOtMBz1FOGMc0DFHTAp2cUmcZFNzuB/SmgJs56VIpFVh0/wp+c0DLQ6d6AOPvAf5+lRBs8irQBIB34/D/wCtSY4pn//S/BDqMUoA5xSc9aXtzWo2A44px2npSEnoaPrQAAUe9N5I5p1MBR60cUfypAcUgF9qOcUnNOHWgQ72pPcUnal70DHcdqMk4UUhJpc0ACntTuB70gBPJoySMUAPPQUoOOnNN5IxSnoMUAOVvfNA4NNGetP7ZpjYDOOec1JkEAYqMELz704AYBFMQ/ockZzUweXHAOPrUPU461OvnYGC2Pr/APXpWGj/0/wROcYpOTTv4aK2HcTHyntS5OOaOgwKU5FADcY60/POaZ3wKdnikADHWlIB/GmjnpRwOtAWHbeKFzSZyKOaBWHCjtyaBgjiigB3B4pB0pMrijPNAx3QUvajg0Y6n1oQC5OcelKCAM00EZ47UucduaYx3HWlGDjPemj3p4YUANAHFPzxk03IxnFA5GcYpiJDj1pNyev60KQp+bpUgjgIyT+v/wBei4W7H//U/BI4HtQP9mjA4z0o6njrWxVgPSlxkYpAeOaOnI4oEHGcCl+tAx3o5oYMbTsY+tHbkUcZ4PFAAfSlBOMikz+AoGMcdaQC9qT6Ui5AoGfypghw5pQQKaDzjNBBxikBIDik5HfrSfNjmjBAppAOHHSlycUgJPFJkGgdh45HFOXim/dOBSgetAC89RSj170i9PoaXkAZp2AUeo71MAhGWPP0qMZHbvTCqZp2Gj//1fwUJBP+eaQYzxSkYz6UmCPc1uyg/h56Up569KTnGRQQQCKQMbkdTS59aOPpRgADJoAXtxzTelOwT3xSZIoEGSeOtL9KQ8DNLyORSAMd6AMdKcBxnPNNwO9NAOx1owQOuab2yKXG480bAhwHfOaOPWk74FJ0yKEMdntSluOKQdMUvA+9QIeOSKaD8xpw5o69eaYxVJBweKfk9zTMZNLgHgUwQ8c9Mc/pUobgfN/KokzgjNWQFx2/Kkyon//W/Bfbjg0zHGRxT3/wpqdcfWtywxke5pO/FOXv9KjHb60WFewMAOBS/rT3Apq96H3AQHPFJnGO1NzxTh98UvIGwOMc0D+dKe9OHQ0CQ0Z5BpAT060i/wCNSr90n/PWmAmcgE0HPSm/xU0k5P8AntQxkgGTnpTj09KatOTlRmkAmKXtwPxprUo6n6U0rsB2cjNOQg0qfcqP+9T2AkyefSjOMY6UL/q1PrUpHB+n9aEMblex+tS/aZxwuQB0qPA5/CnBVIyRSYJtbH//2Q==';
            $date =  Date('Y-m-d');
            $time =  time();

            foreach ($question as $key => $value) {
                if($value->v1pic != 'empty' && $value->v1pic != null){
                    $tstStr = explode(',',$value->v1pic);
                    $tstStr = substr($tstStr[0],0,4);

                    if($tstStr == 'data'){
                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_1.jpg';
                        // $image1 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v1pic, $name );
                        // $image1 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v1pic, $name, $value->outlet_id, 'photo' );
                        $image1 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v1pic, $name, $value->outlet_id, 'photo' );


                    }else{
                        $image1 = $value->v1pic;
                    }
                }else{
                    $image1 = 'empty';
                }

                if($value->v2pic != 'empty' && $value->v2pic != null){
                    $tstStr = explode(',',$value->v2pic);
                    $tstStr = substr($tstStr[0],0,4);

                    if($tstStr == 'data'){
                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_2.jpg';
                        // $image2 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v2pic, $name );   
                        // $image2 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v2pic, $name, $value->outlet_id, 'photo' );
                        $image2 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v2pic, $name, $value->outlet_id, 'photo' );
                    }else{
                        $image2 = $value->v2pic;
                    }                        
                }else{
                    $image2 = 'empty';
                }

                if($value->v3pic != 'empty'  && $value->v3pic != null){
                    $tstStr = explode(',',$value->v3pic);
                    $tstStr = substr($tstStr[0],0,4);

                    if($tstStr == 'data'){
                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_3.jpg';
                        // $image3 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v3pic, $name );
                        // $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                        $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                    }else{
                        $image3 = $value->v3pic;
                    }                    
                }else{
                    $image3 = 'empty';
                }

                if($value->v4pic != 'empty'  && $value->v4pic != null){
                    $tstStr = explode(',',$value->v4pic);
                    $tstStr = substr($tstStr[0],0,4);

                    if($tstStr == 'data'){
                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_pic_4.jpg';
                        // $image4 = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->v4pic, $name );
                        // $image4 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/'.base64_to_jpeg( $value->v4pic, $name, $value->outlet_id, 'photo' );
                        $image3 = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/photo/'.$value->outlet_id.'/'.base64_to_jpeg( $value->v3pic, $name, $value->outlet_id, 'photo' );
                    }else{
                        $image4 = $value->v4pic;
                    }                          
                }else{
                    $image4 = 'empty';
                }     

                DB::table('questions')->where('id',$value->id)->update([
                    'v1pic'=>$image1,
                    'v2pic'=>$image2,
                    'v3pic'=>$image3,
                    'v4pic'=>$image4
                ]);                                        
            }

            foreach ($redemptions as $key => $value) {
                    $tstStr = explode(',',$value->signature);
                    $tstStr = substr($tstStr[0],0,4);

                    if($tstStr == 'data'){
                        $name = $value->outlet_id.$date.$value->month.$value->year.$time.'_signature.png';
                        // $signature = 'https://pmi-iraq-app.optimalonline.co.za/images/'.base64_to_jpeg( $value->signature, $name );
                        $signature = 'https://s3-eu-west-1.amazonaws.com/itap-photo-store/signature/'.$value->outlet_id.'/'.base64_to_jpeg( $value->signature, $name, $value->outlet_id, 'signature' );
                    }else{
                        $signature = $value->signature;
                    }
                
                
                DB::table('redemptions')->where('id',$value->id)->update([
                    'signature'=>$signature
                ]);                    
            }
            //echo realpath($image) . PHP_EOL;

            // $fileName = 'hello'. '.' . 'jpg';
            // $image->move(
            //     base_path() . '/public/', $fileName
            // );
            // $f_url='/'.$fileName;

            // echo $f_url;

            

            // $p1 = Array();
            // $p2 = Array();
            // $p3 = Array();

            // foreach ($alloutletsStats as $key => $value) {
            //     if($value->foc_total == null)$foc_total = 0; else $foc_total = $value->foc_total;
            //     if($value->prize_total == null)$prize_total = 0; else $prize_total = $value->prize_total;

            //     if($value->program_id == '1'){
            //         if($value->started == 1 && $value->completed == 1)
            //             $p1[$value->id] = $prize_total + $foc_total;
            //     }else if($value->program_id == '2'){
            //         if($value->started == 1 && $value->completed == 1)
            //           $p2[$value->id] = $prize_total + $foc_total;
            //     }else if($value->program_id == '3'){
            //         if($value->started == 1 && $value->completed == 1)
            //             $p3[$value->id] = $prize_total + $foc_total;
            //     }                
            // }

            // arsort($p1);
            // arsort($p2);
            // arsort($p3);

            // echo 'Program ID 1 <br> Total Users : '.count($p1). ' <br><br>';
            // $count1 = 0;
            // if(count($p1) > 0)
            // {            
            //     foreach ($p1 as $key => $value) {
            //         $count1++;

            //         //echo 'User ' . $key. ' is in Position ' . $count1 .' of Program 1 with '. $value .' points<br>';

            //         DB::table('outlet_stats')->where('id',$key)->update([
            //             "rank"=>$count1    
            //         ]); 
            //     } 
            // }   
            // echo '<br><br>';         

            // echo 'Program ID 2 <br> Total Users : '.count($p2). ' <br><br>';
            // $count2 = 0;
            // if(count($p2) > 0)
            // {
            //     foreach ($p2 as $key => $value) {
            //         $count2++;

            //         //echo 'User ' . $key. ' is in Position ' . $count2 .' of Program 2 with '. $value .' points<br>';

            //         DB::table('outlet_stats')->where('id',$key)->update([
            //             "rank"=>$count2    
            //         ]); 
            //     }
            // }       
            // echo '<br><br>';      

            // echo 'Program ID 3 <br> Total Users : '.count($p3). ' <br><br>';
            // $count3 = 0;
            // if(count($p3) > 0)
            // {
            //     foreach ($p3 as $key => $value) {
            //         $count3++;

            //         //echo 'User ' . $key. ' is in Position ' . $count3 .' of Program 3 with '. $value .' points<br>';

            //         DB::table('outlet_stats')->where('id',$key)->update([
            //             "rank"=>$count3    
            //         ]); 
            //     }   
            // }            
        }

    // FUNCTIONS
        // ADD A NEW REQUEST
            public function add_request($type,$userID,$data,$app_version,$datetime){
                DB::table('requests')
                    ->insert([
                        "user_id"=>$userID,
                        'app_version'=>$app_version,
                        'request_type'=>$type,
                        'request_data'=>$data,
                        'timedate_of_request'=>$datetime
                    ]);

                return 'success';
            }      
}
