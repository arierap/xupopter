<?php
namespace Xupopter\Providers;

use Xupopter\System\Provider;
use Xupopter\System\IProvider;

class Habitaclia extends Provider implements IProvider
{
    private $domain = "http://www.habitaclia.com";
    public $minResults = 500; // min crawled vod content

    public function crawl ($path)
    {
        $q = $this->getContent($this->domain . $path);

        foreach ($q->find('#listaAds li a[itemprop=name]') as $data)
        {
            $item = $this->parseItem($this->getContent($data->attr("href")));

            if ($item) {
                $this->sendToDB($item);
            }
        }
    }

    private function stringToBool ($str)
    {
        switch ($str)
        {
            case "Sí":
                return true;
            break;
        }
        return false;
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

        // doesnt have images or price
        if (!empty($html->find('.cajon-pedir-foto')->text()) || !empty($html->find('.pvpdesde')->text())) {
            return false;
        }
        
        // ad removed/banned
        if (sizeof($html->find('.listainmueblesleft .listainmuebles:first-child img')) > 0) {
            return false;
        }

        $description = trim($html->find('#js-detail-description')->text());

        $data = [
            'title' => $html->find('.summary-left h1')->text(),
            'location' => trim($html->find('article.location a')->text()),
            'description' => $description,
            'url' => $html->find('link[rel="canonical"]')->attr("href"),
            "price" => $this->strToNumber($html->find('[itemprop="price"]')->text())
        ];
        
        $lastUpdate = trim($html->find('.actualizado.radius')->text());

        preg_match("/\(([0-9\/]+)\)/", $lastUpdate, $matches);
        if (isset($matches[1])) {
            $data["lastUpdate"] = $matches[1];
        }

        foreach ($html->find('#js-feature-container .feature-container li') as $li)
        {
            $text = $li->text();

            if (strpos($text, " m2") !== false) {
                $data["meters"] = $this->strToNumber($li->find("strong")->text());
            } else if (strpos($text, "habitaciones") !== false || strpos($text, "hab.") !== false) {
                $data["rooms"] = (int)$text;
            }
        }

        foreach ($html->find('.caracteristicas li') as $li)
        {
            $text = $li->text();

            if (strpos($text, ":") === false) {
                continue;
            }

            $info = explode(":", $text);

            switch (trim($info[0]))
            {
                case "Número de planta":
                    $data["floor"] = (int)$info[1];
                break;
                case "Aire acondicionado":
                    $data["airConditioner"] = $this->stringToBool(trim($info[1]));
                break;
                case "Calefacción":
                    $data["heating"] = $this->stringToBool(trim($info[1]));
                break;
                case "Parking":
                    $data["parking"] = $this->stringToBool(trim($info[1]));
                break;
                case "Ascensor":
                    $data["elevator"] = $this->stringToBool(trim($info[1]));
                break;
                case "Amueblado":
                    $data["furnished"] = $this->stringToBool(trim($info[1]));
                break;
            }
        }

        foreach ($html->find(".ficha_foto img") as $img)
        {
            $image = str_replace("G.jpg", "XL.jpg", $img->attr("src"));
            $images[] = $image;
        }

        if (sizeof($images) > 0) {
            $data["images"] = $images;
        }

        return $data;
    }
}
