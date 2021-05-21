<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;
use DB;
class Log extends Base
{
    protected $table = 'tracker_log';

    protected $fillable = [
        'session_id',
        'method',
        'path_id',
        'query_id',
        'route_path_id',
        'referer_id',
        'is_ajax',
        'is_secure',
        'is_json',
        'wants_json',
        'error_id',
        'host',
    ];

    public function session()
    {
        return $this->belongsTo($this->getConfig()->get('session_model'));
    }

    public function path()
    {
        return $this->belongsTo($this->getConfig()->get('path_model'));
    }

    public function error()
    {
        return $this->belongsTo($this->getConfig()->get('error_model'));
    }

    public function logQuery()
    {
        return $this->belongsTo($this->getConfig()->get('query_model'), 'query_id');
    }

    public function routePath()
    {
        return $this->belongsTo($this->getConfig()->get('route_path_model'), 'route_path_id');
    }

    public function pageViews($minutes, $results)
    {
        $query = $this->with('path')->with('session')
        ->leftJoin('tracker_sessions', 'tracker_sessions.id', '=', 'session_id')
        ->whereHas('session', function($query){
            $query->where('is_robot', '0');            
            $query->whereHas('agent', function($query){
                $query->where('name', 'not like', '%Linux%');
            });
        })
        ->whereHas('path', function($query){
            $query->where('path', 'not like', '%data%');
            $query->where('path', 'not like', '%jpg%');
            $query->where('path', 'not like', '%png%');
            $query->where('path', 'not like', '%ico%');
            $query->where('path', 'not like', '%css%');
            $query->where('path', 'not like', '%js%');
            $query->where('path', 'not like', '%stats%');
            $query->where('path', 'not like', '%ViewIncrement%');
            $query->where('path', 'not like', '%xml%');
            $query->where('path', 'not like', '%test%');
            $query->where('path', 'not like', '%rss%');
            $query->where('path', 'not like', '%ttf%');
            $query->where('path', 'not like', '%woff%');
        })
        ->where('is_ajax', 0);
        if(substr($minutes->getStart(), 0, 10) != substr($minutes->getEnd(), 0, 10)){
            $query = $query->select(
                $this->getConnection()->raw("DATE(`tracker_log`.created_at) as date, count(*) as total")
            );
            $query = $query->groupBy(
                $this->getConnection()->raw('DATE(`tracker_log`.created_at), concat(`tracker_sessions`.`client_ip`, `tracker_log`.`path_id` )')
            );
        }else{
            $query = $query->select(
                $this->getConnection()->raw("substr( `tracker_log`.`created_at`, 12,2) as date, count(*) as total")
            );
            $query = $query->groupBy(
                $this->getConnection()->raw('left( `tracker_log`.`created_at`, 13)')
            );
        }
        $query = $query->period($minutes, 'tracker_log')
                        ->orderBy('date');

        if ($results) {
            if(substr($minutes->getStart(), 0, 10) != substr($minutes->getEnd(), 0, 10)){
                $query = $query->get();
                $last_data = [];
                foreach($query as $item){
                    $last_data[$item->date]['date'] =  $item->date; 
                    if(!isset($last_data[$item->date]['total'])) $last_data[$item->date]['total'] = 0;                   
                    $last_data[$item->date]['total'] ++;
                }
                return array_values($last_data);
            }else{
                return $query->get();
            }
        }

        return $query;
    }

    public function pageViewsByHost($minutes, $results)
    {
        $query = $this->leftJoin('tracker_sessions', 'tracker_sessions.id', '=', 'session_id')
        ->whereHas('session', function($query){
            $query->where('is_robot', '0');            
            $query->whereHas('agent', function($query){
                $query->where('name', 'not like', '%Linux%');
            });
        })
        ->whereHas('path', function($query){
            $query->where('path', 'not like', '%data%');
            $query->where('path', 'not like', '%jpg%');
            $query->where('path', 'not like', '%png%');
            $query->where('path', 'not like', '%ico%');
            $query->where('path', 'not like', '%css%');
            $query->where('path', 'not like', '%js%');
            $query->where('path', 'not like', '%stats%');
            $query->where('path', 'not like', '%ViewIncrement%');
            $query->where('path', 'not like', '%xml%');
            $query->where('path', 'not like', '%test%');
            $query->where('path', 'not like', '%rss%');
            $query->where('path', 'not like', '%ttf%');
            $query->where('path', 'not like', '%woff%');
        })
        ->where('is_ajax', 0);

            $query = $query->select(
                $this->getConnection()->raw("host, count(*) as total, concat(date(`tracker_log`.created_at),`tracker_sessions`.`client_ip`, `tracker_log`.`path_id`) as dest ")
            );
            $query = $query->groupBy( 
                            $this->getConnection()->raw("host, dest")
            );
        $query = $query->period($minutes, 'tracker_log')
                        ->orderBy('host')
                        ->orderBy('dest');
        
        $count = DB::table( DB::raw("({$query->toSql()}) as sub") )
        ->mergeBindings($query->getQuery()) // you need to get underlying Query Builder
        ->select(
               $this->getConnection()->raw("host, count(*) as total")
            );
        $count = $count->groupBy( 
                            $this->getConnection()->raw("host")
            )
        // ->where(..) correct

        ->get();dump($count);exit;
        
        $query = $query->get();
        $last_data = [];
        $temp = '';$i=0;dump($query[2]);
        foreach($query as $index => $item){
            if($item->dest == $temp) continue;
            $temp = $item->dest; 
            if(!isset($query[$item->host])) $query[$item->host] = [];           
            $query[$item->host]->host =  $item->host; 
            if(!isset($query[$item->host]['total'])) $query[$item->host]['total'] = 0;                   
            $query[$item->host]['total'] ++;
            unset($query[$index]);
        }
        $query = array_values($query);

        return $query;
    }

    public function pageViewsByCountry($minutes, $results)
    {
        $query =
            $this
            ->select(
                'tracker_geoip.country_name as label',
                $this->getConnection()->raw('count(tracker_log.id) as value')
            )
            ->join('tracker_sessions', 'tracker_log.session_id', '=', 'tracker_sessions.id')
            ->join('tracker_geoip', 'tracker_sessions.geoip_id', '=', 'tracker_geoip.id')
            ->groupBy('tracker_geoip.country_name')
            ->period($minutes, 'tracker_log')
            ->whereNotNull('tracker_sessions.geoip_id')
            ->orderBy('value', 'desc');

        if ($results) {
            return $query->get();
        }

        return $query;
    }

    public function errors($minutes, $results)
    {
        $query = $this
                    ->with('error')
                    ->with('session')
                    ->with('path')
                    ->period($minutes, 'tracker_log')
                    ->whereNotNull('error_id')
                    ->orderBy('created_at', 'desc');

        if ($results) {
            return $query->get();
        }

        return $query;
    }

    public function allByRouteName($name, $minutes = null)
    {
        $result = $this
                    ->join('tracker_route_paths', 'tracker_route_paths.id', '=', 'tracker_log.route_path_id')

                    ->leftJoin(
                        'tracker_route_path_parameters',
                        'tracker_route_path_parameters.route_path_id',
                        '=',
                        'tracker_route_paths.id'
                    )

                    ->join('tracker_routes', 'tracker_routes.id', '=', 'tracker_route_paths.route_id')

                    ->where('tracker_routes.name', $name);

        if ($minutes) {
            $result->period($minutes, 'tracker_log');
        }

        return $result;
    }
}
