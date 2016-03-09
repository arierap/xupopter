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

        foreach ($q->find('#search-listing tr.expanded') as $data)
        {
            $item = $this->parseItem($this->getContent($data->attr("data-url")));

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

        /*
            transform http://a.ftcs.es/inmesp/anuncio/2015/04/03/135151707/253141017.jpg/w_0/c_690x518/p_1/
            to        http://a.ftcs.es/inmesp/anuncio/2015/04/03/135151707/253141017.jpg
        */
    	foreach ($html->find('#containerSlider img') as $img)
        {
    		$src = $img->attr("data-src");

    		if (empty($src)) {
    			$src = $img->attr("src");
    		}

    		$path = explode(".jpg", $src);
    		$images[] = $path[0] . ".jpg";
    	}

    	$data = [
    		'title' => trim($html->find('.property-title')->text()),
    		'description' => trim($html->find('#ctl00_ddDescription .detail-section-content')->text()),
    		'images' => $images,
    		'location' => trim($html->find('.section.section--noBorder .detail-section-content')->text()),
    		'price' => $this->strToNumber($html->find('#priceContainer')->text()),
    		'meters' => $this->strToNumber($html->find('#litSurface b')->text()),
    		'floor' => (int)$html->find('#litFloor')->text(),
    		'url' => $html->find('link[rel="canonical"]')->attr("href")
    	];

    	foreach ($html->find('.detail-extras li') as $li) {
    		$text = trim($li->text());
    		switch ($text) {
    			case "Ascensor":
    				$data["elevator"] = true;
    			break;
    		}
    	}

    	if ($data["meters"] == 0 || empty($data["description"])) {
    		return false;
    	}

    	return $data;

	}
}
