<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
use Carbon\Carbon;

class lockCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lockcalls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command makes sure that the calls of the current month will be locked on the 1st of the following month.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // get current month and tinestamp
        $CURRENT_YEAR = date('Y');
        $CURRENT_MONTH = date('n');
        if($CURRENT_MONTH > 1)
        {
            $PREVIOUS_MONTH = $CURRENT_MONTH-1;
            $PREVIOUS_YEAR = $CURRENT_YEAR;
        }
        else
        {
            $PREVIOUS_MONTH = 12;
            $PREVIOUS_YEAR = $CURRENT_YEAR-1;
        }
        $TIMESTAMP = date("Y-m-d H:i:s");

        /// pull stats to be closed
        $STATS = DB::table('outlet_stats')->where('month',$PREVIOUS_MONTH)->where('year',$PREVIOUS_YEAR)->where('completed','0')->whereNull('deleted_at')->get();

        foreach ($STATS as $key => $value) {
            // get corresponding questions field of stats
            $QUESTIONS = DB::table('questions')->where('outlet_id',$value->outlet_id)->where('month',$value->month)->where('year',$value->year)->whereNull('deleted_at')->first();

            // update the stats row
            DB::table('outlet_stats')->where('id',$value->id)->update([
                "prize_total"           => $value->prize_total + $QUESTIONS->cep + $QUESTIONS->msv1p + $QUESTIONS->msv2p + $QUESTIONS->rapp + $QUESTIONS->nplp + $QUESTIONS->satp1 + $QUESTIONS->satp2,
                "foc_total"             => $value->foc_total + $QUESTIONS->av1p + $QUESTIONS->av2p + $QUESTIONS->av3p + $QUESTIONS->av4p + $QUESTIONS->vv1p1 + $QUESTIONS->vv1p2 + $QUESTIONS->vv2p1 + $QUESTIONS->vv2p2 + $QUESTIONS->vv3p1 + $QUESTIONS->vv3p2 + $QUESTIONS->vv4p1 + $QUESTIONS->vv4p2,
                "rap_month"             => $QUESTIONS->rapp,
                "npl_month"             => $QUESTIONS->nplp,
                "a_month"               => $QUESTIONS->av1p + $QUESTIONS->av2p + $QUESTIONS->av3p + $QUESTIONS->av4p,
                "v_month"               => $QUESTIONS->vv1p1 + $QUESTIONS->vv1p2 + $QUESTIONS->vv2p1 + $QUESTIONS->vv2p2 + $QUESTIONS->vv3p1 + $QUESTIONS->vv3p2 + $QUESTIONS->vv4p1 + $QUESTIONS->vv4p2,
                "ms_month"              => $QUESTIONS->msv1p + $QUESTIONS->msv2p,
                "ce_month"              => $QUESTIONS->cep,
                "sat_month"             => $QUESTIONS->satp1 + $QUESTIONS->satp2,
                "consumer_engagement"   => $value->consumer_engagement + $QUESTIONS->cep,
                "mystery_shopper"       => $value->mystery_shopper + $QUESTIONS->msv1p + $QUESTIONS->msv2p,
                "visibility"            => $value->visibility + $QUESTIONS->vv1p1 + $QUESTIONS->vv1p2 + $QUESTIONS->vv2p1 + $QUESTIONS->vv2p2 + $QUESTIONS->vv3p1 + $QUESTIONS->vv3p2 + $QUESTIONS->vv4p1 + $QUESTIONS->vv4p2,
                "availability"          => $value->availability + $QUESTIONS->av1p + $QUESTIONS->av2p + $QUESTIONS->av3p + $QUESTIONS->av4p,
                "rap"                   => $value->rap + $QUESTIONS->rapp, 
                "npl"                   => $value->npl + $QUESTIONS->nplp,
                "sat"                   => $value->sat + $QUESTIONS->satp1 + $QUESTIONS->satp2,
                "started"               => "1",
                "completed"             => "1",
                "updated_at"            => $TIMESTAMP
            ]);
        }
    }
}
