<?php
namespace cloudPWR;

class NYSIISTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers cloudPWR\NYSIIS::__construct
     */
    public function testConstruct()
    {
        $nysiis = new \cloudPWR\NYSIIS();
        $this->assertTrue($nysiis instanceof \cloudPWR\NYSIIS);
        
        $nysiis = new \cloudPWR\NYSIIS(6);
        $this->assertTrue($nysiis instanceof \cloudPWR\NYSIIS);
        
        $nysiis = new \cloudPWR\NYSIIS(-1);
        $this->assertTrue($nysiis instanceof \cloudPWR\NYSIIS);
        
        $output_length_test = array(
            'foobar',
            2.5,
        );
        foreach ($output_length_test as $test_value) {
            $caught_exception = false;
            try {
                $nysiis = new \cloudPWR\NYSIIS($test_value);
            } catch (\Exception $ex) {
                $caught_exception = true;
                $this->assertEquals(
                    'cloudPWR\\NYSIIS was created with an invalid max_output_length.',
                    $ex->getMessage()
                );
            }
            if (!$caught_exception) {
                $this->fail(
                    'NYSIIS constructor accepted an invalid max output length '.
                    '"'.(string)$test_value.'"'
                );
            }
        }
    }
    
    /**
     * @covers cloudPWR\NYSIIS::encode
     */
    public function testEncode()
    {
        $nysiis = new \cloudPWR\NYSIIS();
        
        $encode_test = array(
            "Bishop" => "BASAP",
            "brown sr" => "BRANSR",
            "browne III" => "BRAN",
            "browne IV" => "BRANAV",
            "Carlson" => "CARLSAN",
            "Carr" => "CAR",
            "Chapman" => "CAPNAN",
            "de Sousa" => "DASAS",
            "D'Souza" => "DSAS",
            "Franklin" => "FRANCLAN",
            "Greene" => "GRAN",
            "Harper" => "HARPAR",
            "Hoyle-Johnson" => "HAYLAJANSAN",
            "Jacobs" => "JACAB",
            "knight" => "NAGT",
            "Larson" => "LARSAN",
            "Lawrence" => "LARANC",
            "Lawson" => "LASAN",
            "Louis XVI" => "LASXV",
            "Lynch" => "LYNC",
            "Mackenzie" => "MCANSY",
            "Matthews" => "MAT",
            "McCormack" => "MCARNAC",
            "McDaniel" => "MCDANAL",
            "McDonald" => "MCDANALD",
            "Mclaughlin" => "MCLAGLAN",
            "mitchell" => "MATCAL",
            "Morrison" => "MARASAN",
            "O'Banion" => "OBANAN",
            "O'Brien" => "OBRAN",
            "o'daniel" => "ODANAL",
            "Richards" => "RACARD",
            "Silva" => "SALV",
            "Vaughan Williams" => "VAGANWALAN",
            "Watkins" => "WATCAN",
            "Wheeler" => "WALAR",
            "Willis" => "WAL",
        );
        
        foreach ($encode_test as $test_name => $expected_encoded) {
            $this->assertEquals(
                $expected_encoded,
                $nysiis->encode($test_name)
            );
        }
    }
    
    /**
     * @covers cloudPWR\NYSIIS::encode
     */
    public function testEncodeUnicode()
    {
        $nysiis = new \cloudPWR\NYSIIS();
        
        // https://en.wikipedia.org/wiki/List_of_most_common_surnames_in_North_America#Mexico_.28Mexican.29
        $encode_test = array(
            "Martínez" => "MARTAN",
            "García" => "GARC",
            "Hernandez" => "HARNAND",
            "González" => "GANSAL",
            "López" => "LAP",
            "Rodríguez" => "RADRAG",
            "Pérez" => "PAR",
            "Sánchez" => "SANC",
            "Ramírez" => "RANAR",
            "Flores" => "FLAR",
        );
        
        foreach ($encode_test as $test_name => $expected_encoded) {
            $this->assertEquals(
                $expected_encoded,
                $nysiis->encode($test_name)
            );
        }
    }
    
    /**
     * @covers cloudPWR\NYSIIS::encode
     */
    public function testEncodeError()
    {
        $encode_test = array(
            -1,
            3.14,
            0xdeadbeaf,
        );
        
        $nysiis = new \cloudPWR\NYSIIS();
        foreach ($encode_test as $test_value) {
            $caught_exception = false;
            try {
                $nysiis->encode($test_value);
            } catch (\Exception $ex) {
                $caught_exception = true;
                $this->assertEquals(
                    'cloudPWR\\NYSIIS::encode was passed an invalid name.',
                    $ex->getMessage()
                );
            }
            if (!$caught_exception) {
                $this->fail(
                    'NYSIIS encode accepted an invalid name: '.
                    '"'.(string)$test_value.'"'
                );
            }
        }
    }
}
