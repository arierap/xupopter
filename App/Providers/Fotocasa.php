<?php
namespace Xupopter\Providers;

use Xupopter\System\Provider;
use Xupopter\System\IProvider;

class Fotocasa extends Provider implements IProvider
{
    private $domain = "http://www.fotocasa.es";

    public function crawl ($path)
    {
        $q = $this->getContent($this->domain . $path);

        foreach ($q->find('script') as $data)
        {
            $json = $data->text();
            if (strpos($json, "__INITIAL_PROPS__") === false) {
                continue;
            }
            $json = str_replace('window.__INITIAL_PROPS__=', "", $json);
            $json = json_decode($json)->initialSearch->result->realEstates;

            foreach ($json as $house) {
                $item = $this->parseItem($house);

                if ($item) {
                    $this->sendToDB($item);
                }
            }
        }
    }



	/**
     * Converts provider output to db's input format
     *
     * @param object $json
     *
     * @return mixed (array/boolean)
     */
    public function parseItem ($html)
    {
    	$images = [];

        /*
            transform http://a.ftcs.es/inmesp/anuncio/2015/04/03/135151707/253141017.jpg/w_0/c_690x518/p_1/
            to        http://a.ftcs.es/inmesp/anuncio/2015/04/03/135151707/253141017.jpg
        */
    	foreach ($json->multimedia as $img)
        {
            if ($img->type != 'image') {continue;}
            $src = $img->src;

            $path = explode(".jpg", $src);
            $images[] = $path[0] . ".jpg";
        }

        $features = [];
        foreach ($json->features as $feature) {
            $features[$feature->key] = $feature->value;
        }

        $data = [
            'title' => $json->buildingType . "-". $json->id,
            'description' => $json->description,
            'images' => $images,
            'location' => $json->location,
            'price' => (double)$json->price,
            'meters' => (double)$features['surface'],
            'url' => $this->domain . "/es/" . $json->detail->{'es-ES'}
        ];

        if ($features['rooms']) {
            $data['rooms'] = $features['rooms'];
        }

    	if ($data["meters"] == 0 || empty($data["description"])) {
    		return false;
    	}

    	return $data;

	}
}
