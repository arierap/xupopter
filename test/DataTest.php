<?php
class DataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider houseProvider
     */
    public function testHouseExtraction($provider, $expected)
    {
        $class = "\\Xupopter\\Providers\\" . $provider;
		$provider = new $class();
		$item = $provider->crawlItem($expected->url);

        if (!$item) {
            throw new Exception("Error Processing Request", 1);
        }

        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $item[$k], "Check $k");
        }
    }

    public function houseProvider()
    {
        $providers = [
            "Habitaclia",
			"Idealista",
			"Fotocasa",
			"Pisos",
        ];
        $expectedResults = [];

        foreach ($providers as $provider) {
            $result = json_decode(file_get_contents(dirname(__FILE__) . "/data/" . strtolower($provider) . ".json"));
            $expectedResults[] = [$provider, $result];
        }

        return $expectedResults;
    }
}
?>
