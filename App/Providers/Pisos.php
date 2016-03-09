<?php
namespace Xupopter\Providers;

use Xupopter\System\Provider;
use Xupopter\System\IProvider;

class Pisos extends Provider implements IProvider
{
    private $domain = "http://www.pisos.com";
    private $itemProps = [
        "postalCode",
        "latitude",
        "longitude",
    ];

    public function crawl ($path)
    {
        $q = $this->getContent($this->domain . $path);

        foreach ($q->find('[itemprop="photo"] [itemprop="url"]') as $data)
        {
            $item = $this->parseItem($this->getContent($this->domain . $data->attr("content")));

            if ($item) {
                $this->sendToDB($item);
            }
        }
    }

    /**
     * Converts provider output to db's input format
     *
     * @param QueryPath $html
     *
     * @return mixed (array/boolean)
     */
    public function parseItem ($html)
    {
        $images = [];
    	$data = [
    		'title' => trim($html->find('h1.title')->text()),
    		'description' => trim($html->find('.description')->text()),
            'price' => $this->strToNumber($html->find('.jsPrecioH1')->eq(0)->text()),
    		'url' => $html->find('link[rel="canonical"]')->attr("href")
    	];

        foreach ($this->itemProps as $prop)
        {
            $propVal = $html->find('[itemprop="' . $prop . '"]')->attr("content");

            if (!empty($propVal)) {
                $data[$prop] = $propVal;
            }
        }

        // try to get the exact address
        $location = $html->find('[itemprop="streetAddress"]')->attr("content");

        if (empty($location)) {
            $location = $html->find('meta[itemprop="name"]')->attr("content");
            $location = str_replace("Piso en venta en ", "", $location);
            $location = str_replace("Piso en alquiler en ", "", $location);
        }

        $data['location'] = $location . ", " . $html->find('h2.position')->text();

    	foreach ($html->find('.characteristics .item') as $item)
        {
            $text = $item->text();

            $this->parseHouseInfo($text, $data);
    	}

        // skip retards that dont even fill the apartment meters
        if (!isset($data["meters"]) || $data["meters"] < 1) {
            return false;
        }

        /*
        from http://fotos.imghs.net/s/1030/129/1030_27926263129_1_2015112416580031250.jpg
        to http://fotos.imghs.net/xl/1030/129/1030_27926263129_1_2015112416580031250.jpg
        */
        foreach ($html->find("#basic img") as $img)
        {
            $image = str_replace(".net/s/", ".net/xl/", $img->attr("src"));

            // skip the default photos
            if (strpos($image, "nofoto_mini.jpg") !== false || strpos($image, "blank1x1.png") !== false || strpos($image, "Images/assets") !== false) {
                continue;
            }

            $images[] = $image;
        }

        if (sizeof($images) > 0) {
            $data["images"] = $images;
        }

    	return $data;
    }
}
