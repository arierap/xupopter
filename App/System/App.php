<?php

namespace Xupopter\System;

class App
{
	public static $config;
    public static $sess;

	/**
	 * Gets/sets a config key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public static function config ($key, $value = null)
	{
		if (empty($value)) {
			return self::$config[$key];
		} else {
			self::$config[$key] = $value;
		}
	}

    /**
     * Gets/sets a sess key
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function sess ($key, $value = null)
    {
        if (empty($value)) {
            return self::$sess[$key];
        } else {
            self::$sess[$key] = $value;
        }
    }

	/**
	 * On debug mode prints lines
	 *
	 * @param string line line to prin
	 * @return void
	 */
	public static function debug ($line)
    {
		if (self::config("debug")) {
			echo $line."\n";
		}
	}

    /**
     * Executes a provider
     * @param string $pName
	 * @param array $paths list of paths to crawl
     *
     * @return void
     */
	public static function runProvider ($pName, $paths, $callback = null)
    {
        self::debug("*********************************\n**** Starting provider " . $pName . " *****\n*********************************");

		self::config("callback", $callback);
		$class = "\\Xupopter\\Providers\\" . $pName;

		$provider = new $class();
		$provider->start($paths);
    }
}
