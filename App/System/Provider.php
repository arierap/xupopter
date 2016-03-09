<?php

namespace Xupopter\System;

use Exception;

class Provider
{
	private $requiredFields = [
		'title' => 'string',
        'description' => 'string',
        'location' => 'string',
		'url' => 'string',
        'price' => 'double',
        'meters' => 'double',
        'images' => 'array',
	];

	private $optionalFields = [
		'lastUpdate' => 'string',
		'postalCode' => 'string',
		'longitude' => 'string',
		'latitude' => 'string',
		'rooms' => 'integer',
        'floor' => 'integer',
        'airConditioner' => 'boolean',
        'heating' => 'boolean',
        'parking' => 'boolean',
        'elevator' => 'boolean',
        'furnished' => 'boolean',
	];

	/**
	* Starts crawling the given provider paths
	*	@param array $paths
	*/
    public function start ($paths)
    {
        foreach ($paths as $path) {
            $this->crawl($path);
        }
    }

	public function crawlItem ($url)
	{
		$html = Utils::curl($url);
		return $this->parseItem(htmlqp($html));
	}

	/**
	* Returns the provider's name that is being executed
	*	@return string
	*/
    private function getProviderName () {
        return str_replace('Xupopter\\Providers\\',"", static::class);
    }

	/**
	* Converts string numbers like 45.000 to floats 45000
	*	@param string $number
	*
	*	@return float
	*/
	protected function strToNumber ($number) {
		return (float)str_replace(".", "", $number);
	}

	/**
	*	Tries to detect if the given string is a house property
	*	@param string $text string to search in
	*	@param array $data
	*/
	protected function parseHouseInfo ($text, &$data)
	{
		if (strpos($text, " m2") !== false || strpos($text, " m²") !== false) {
			$data["meters"] = $this->strToNumber($text);
		} else if (strpos($text, "ª ") !== false) {
			$data["floor"] = (int)$text;
		}
	}

	/**
	*	Checks if the given item(house) must be skipped
	*	@param array $item
	*
	*	@return boolean
	*/
    private function compliesFilters ($item)
    {
        foreach (App::config("avoid") as $field => $words)
        {
            if ($field == "text") {

                $complies = $this->compliesFilter($item, "title", $words);
                if (!$complies) { return false;}

                if (!isset($item["description"])) {
                    continue;
                }

                $complies = $this->compliesFilter($item, "description", $words);
                if (!$complies) { return false;}

            } else if (!isset($item[$field])) {
                continue;
            } else {
                $complies = $this->compliesFilter($item, $field, $words);
                if (!$complies) { return false;}
            }
        }

        return true;
    }

    private function compliesFilter ($item, $field, $words)
    {
        foreach ($words as $word) {
            if (strpos(strtolower($item[$field]), $word) !== false) {
                return false;
            }
        }
        return true;
    }

	/**
	 * Sends an item to DB queue list after checking item's integrity
	 *
	 * @param array $item
	 * @return boolean
	 */
	public function sendToDB ($item)
    {
		$item = $this->checkIntegrity($item);

        if (!$this->compliesFilters($item)) {
            return;
        }

        $item["crawler"] = "Xupopter: " . $this->getProviderName();

		App::debug(json_encode($item) . "\n");

		if (!App::config("debug")) {
            $callback = App::config("callback");
			$callback($item);
        }
	}

	/**
	 * Verifies that the object begin sent to kraken has the required fields
	 *
	 * @param array $item
	 *
	 * @return array
	 * @throws ProviderException if integrity check failed
	 */
	public function checkIntegrity ($item)
    {
		foreach ($this->requiredFields as $field => $type)
        {
			if (!isset($item[$field]) || empty($item[$field]) || gettype($item[$field]) != $type) {
				throw new ProviderException(ProviderException::INVALID_INTEGRITY, $field . ':' . gettype($item[$field]) . var_dump($item));
			}

			switch ($field) {
				case 'images':
					foreach ($item[$field] as $k => &$title) {
                        $title = trim($title);

                        if (empty($title)) {
                            throw new ProviderException(ProviderException::INVALID_INTEGRITY, $field.':'.var_dump($item));
                        }
					}
					break;
			}
		}

		// validate optional fields
		foreach ($item as $k => $v) {
			if (isset($this->optionalFields[$k]) && gettype($v) != $this->optionalFields[$k]) {
				throw new ProviderException(ProviderException::INVALID_INTEGRITY, $k);
			}
		}

		return $item;
	}

	/**
	 * delete downloaded file after parsing it
	 *
	 * @return void
	 */
	function deleteCache () {
		if (!App::config("debug") && isset($this->tmpUncompressedFile)) {
			unlink($this->tmpUncompressedFile);
		}
	}

	/**
	 * Downloads html and temporary saves them in debug mode
	 *
	 * @param string $url
	 * @param array $options
	 *
	 * @return QueryPath
	 */
	function getContent ($url, $options = []) {

		if (App::config("debug")) {
			$fileCache = App::config("tmp").md5($url);
			if (file_exists($fileCache))
            {
                $html = file_get_contents($fileCache);

                if (isset($options["encoding"])) {
                    $html = $this->fixEncoding($html);
                }

				return htmlqp($html);
			}
		}

		$html = Utils::curl($url);

		// lower string size
		if (isset($options["onlyBody"]))
        {
			if (strpos('<body>', $html) !== false) {
				$tmp = explode('<body>', $html);
				$tmp = explode('</body>', $tmp[1]);
				$html = $tmp[0];
			}
		}

		if (App::config("debug")) {
			file_put_contents($fileCache, $html);
		}

		if (isset($options["encoding"])) {
            $html = $this->fixEncoding($html);
        }

		return htmlqp($html);
	}

    private function fixEncoding ($html)
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        foreach ($doc->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $doc->removeChild($item);
            }
        }
        $doc->encoding = 'UTF-8';
        return $doc->saveHTML();
    }
}

class ProviderException extends Exception {

	const INVALID_INTEGRITY = "invalid object integrity";
	const CRAWLING_ERROR = "provider couldn't be crawled properly";

	public function __construct($constant, $extraInfo, $code = 0, Exception $previous = null) {

		parent::__construct($constant.': '.$extraInfo, $code, $previous);
	}
}
