<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Support;

use Illuminate\Support\Facades\Request;
use PragmaRX\Tracker\Support\Minutes;
use Session as LaravelSession;

class Session
{
    private $minutes;

    public function __construct()
    {
        LaravelSession::put('tracker.stats.page', $this->getValue('page', 'summary'));
        $date_range = $this->getValue('date_range', strftime("%Y-%m-%d",strtotime("-7day ".date("Y-m-d"))).' ~ ' .date("Y-m-d")) ;
        
        LaravelSession::put('tracker.stats.date_range', $date_range);
        $date = explode('~', $date_range);
        $days=round((strtotime(trim($date[1])) - strtotime(trim($date[0])) )/3600/24) ;
        $date = trim($date[1]);
        LaravelSession::put('tracker.stats.date', $date);
        LaravelSession::put('tracker.stats.days', $days);
        if($this->getValue('page') == 'visits')
        {
            LaravelSession::put('tracker.stats.days', 1);
        }
        

        $this->minutes = new Minutes(60 * 24 * LaravelSession::get('tracker.stats.days'));
    }

    /**
     * @return Minutes
     */
    public function getMinutes()
    {
        return $this->minutes;
    }

    public function getValue($variable, $default = null)
    {
        if (Request::has($variable)) {
            $value = Request::get($variable);
        } else {
            $value = LaravelSession::get('tracker.stats.'.$variable, $default);
        }

        return $value;
    }
}
