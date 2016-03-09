<?php
namespace Xupopter\Providers;

use Xupopter\System\Provider;
use Xupopter\System\IProvider;

class Idealista extends Provider implements IProvider
{
    private $domain = "http://www.idealista.com";

    public function crawl ($path)
    {
        $q = $this->getContent($this->domain . $path);

        foreach ($q->find('.item-link') as $data)
        {
            $item = $this->parseItem($this->getContent($this->domain . $data->attr("href")));

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

        // get ch var from og image (required to display the images)
        $ogImage = $html->find('[name="og:image"]')->attr("content");

        if (empty($ogImage)) {
            return false;
        }

        parse_str(parse_url($ogImage)["query"], $query);
        $imageCh = $query["ch"];

        /*
            transform http://img3.idealista.com/thumbs,W,H,wi,+tSLyO%2BcnvWFQ1vfQ1%2FQRH6EBc9TEzAKu5PmhgV%2
            to        http://img3.idealista.com/thumbs?wi=1500&he=0&en=%2BtSLyO%2BcnvWFQ1vfQ1%2FQRH6EBc9TEzAKu5PmhgV%2&ch=2106166706
        */
    	foreach ($html->find('#main-multimedia img') as $img) {
            $image = str_replace("http://img3.idealista.com/thumbs,W,H,wi,+", "", $img->attr("data-service"));

    		$images[] = "http://img3.idealista.com/thumbs?wi=1500&he=0&en=%2B" . urlencode($image) . "&ch=" . $imageCh;
    	}

        $title = trim($html->find('h1.txt-bold span')->text());
        $location = str_replace("Piso en venta en ", "", $title);
        $location = str_replace("Piso en alquiler en ", "", $location);

    	$data = [
    		'title' => $title,
    		'description' => trim($html->find('.adCommentsLanguage.expandable')->text()),
    		'images' => $images,
    		'location' => $location,
    		'price' => $this->strToNumber($html->find('#main-info .txt-big.txt-bold')->eq(0)->text()),
    		'url' => $html->find('#share-link')->attr("href")
    	];

        foreach ($html->find('#fixed-toolbar .info-data > span') as $item)
        {
            $text = $item->text();

            $this->parseHouseInfo($text, $data);
    	}

    	if (!isset($data["meters"]) || $data["meters"] == 0 || empty($data["description"])) {
    		return false;
    	}

    	return $data;

	}
}
