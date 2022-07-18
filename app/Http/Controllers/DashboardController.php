<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CityRequest;

class DashboardController extends Controller
{
    protected $client;

    public function __construct()
    {
        //Initialize the client on the constructor so we can use it anywhere
        $this->client = $client = new \GuzzleHttp\Client(['base_uri' => env('API_URL'), 'verify' => false, 'headers' => ['api_key' => env('API_KEY')], ]);
    }

    public function index() {
        //Get all cities to populate the select
        $cities = $this->client->get('maestro/municipios')->getBody()->getContents(); 
        $filteredCities = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cities), true);
        
        $allCities = [];
      
        //Filter the data so it correctly travels to view
        foreach($filteredCities as $city) {
            $allCities[] = ['id' => $city['id'], 'value' => $city['nombre']];
        }
        
        return view('front.home.index', compact('allCities'));
    }

    public function getTemperature(CityRequest $request) {
        //Get the data for the selected city
        $temperature = json_decode($this->client->get('prediccion/especifica/municipio/diaria/' . str_replace('id', '',$request->city_id))->getBody()->getContents()); 

        if($temperature->estado != 200) {
            return response()->json(['retCode' => '1', 'msj' => 'Request went wrong']);
        }

        $data = $this->client->get($temperature->datos)->getBody()->getContents(); 

        //Filter the data and use it as an array
        $filteredData = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), true);

        $filteredPredictions = [];
        foreach($filteredData as $index => $predictions) {
            foreach($predictions['prediccion'] as $prediction) {
                foreach($prediction as $predictionValues) {
                    //For each data value the api returns, we save it in array and pass all the filtered data to view

                    //precipitationPossibility
                    $precipitationPossibilities = [];
                    foreach($predictionValues['probPrecipitacion'] as $possibilityPrecipitation) {
                        if(isset($possibilityPrecipitation['periodo'])) {
                            $precipitationPossibilities[$possibilityPrecipitation['periodo']] = $possibilityPrecipitation['value'] . '%';
                        } else {
                            $precipitationPossibilities['All day'] = $possibilityPrecipitation['value'] . '%';
                        }
                    }

                    //snow
                    $snow = [];
                    foreach($predictionValues['cotaNieveProv'] as $snowQuota) {
                        if(isset($snowQuota['periodo'])) {
                            $snow[$snowQuota['periodo']] = $snowQuota['value'];
                        } else {
                            $snow['All day'] = $snowQuota['value'];
                        }
                    }

                    //sky status
                    $skyStatus = [];
                    foreach($predictionValues['estadoCielo'] as $skyState) {
                        if(isset($skyState['periodo'])) {
                            $skyStatus[$skyState['periodo']] = $skyState['descripcion'];
                        } else {
                            $skyStatus['All day'] = $skyState['descripcion'];
                        }
                    }

                    //wind
                    $wind = [];
                    foreach($predictionValues['viento'] as $windStatus) {
                        if(isset($windStatus['periodo'])) {
                            $wind[$windStatus['periodo']] = $windStatus['velocidad'] . ' Km/h ' . $windStatus['direccion'];
                        } else {
                            $wind['All day'] = $windStatus['velocidad'] . ' Km/h ' . $windStatus['direccion'];
                        }
                    }

                    //temperature
                    $temperature = [];
                    foreach($predictionValues['temperatura']['dato'] as $tempStatus) {
                        $temperature[$tempStatus['hora'] . ':00'] = $tempStatus['value'] . ' °C';
                    }

                    if(sizeof($temperature) == 0) {
                        $temperature['All day max'] = $predictionValues['temperatura']['maxima'] . ' °C';
                        $temperature['All day min'] = $predictionValues['temperatura']['minima'] . ' °C';
                    }

                    //humity
                    $humity = [];
                    foreach($predictionValues['humedadRelativa']['dato'] as $humityStatus) {
                        $humity[$humityStatus['hora'] . ':00'] = $humityStatus['value'] . '%';
                    }

                    if(sizeof($humity) == 0) {
                        $humity['All day max'] = $predictionValues['humedadRelativa']['maxima'] . ' %';
                        $humity['All day min'] = $predictionValues['humedadRelativa']['minima'] . ' %';
                    }

                    //With all the collected data, we save it in the community array
                    $filteredPredictions[date('d-m-Y', strtotime($predictionValues['fecha']))] = [
                        'Precipitation' => $precipitationPossibilities,
                        'Snow' => $snow,
                        'Sky' => $skyStatus,
                        'Wind' => $wind,
                        'Temperature' => $temperature,
                        'Humity' => $humity
                    ];
                }
            }
        }
        
        return $filteredPredictions;
    }
}
