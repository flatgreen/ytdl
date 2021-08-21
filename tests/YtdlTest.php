<?php

use BrianHenryIE\ColorLogger\ColorLogger;
use Flatgreen\Ytdl\Options;
use Flatgreen\Ytdl\Ytdl;
use PHPUnit\Framework\TestCase;

final class YtdlTest extends TestCase
{
    private $logger;
    private $options;

    public function setUp(): void
    {
        $this->logger = new ColorLogger;
        $this->options = new Options;
    }


   public function testCreate(){
       $opt = new Options();
       $ytdl = new Ytdl($opt);
       $this->assertInstanceOf(Ytdl::class, $ytdl);
   }

   public function testRun(){
       $opt = new Options();
       $opt->setOptions(['--version']);
       $ytdl = new Ytdl($opt, $this->logger);
       $actual = $ytdl->run();
       // it's a date !
       $this->assertMatchesRegularExpression('/^(19|20)\d\d\.(0[1-9]|1[012])\.(0[1-9]|[12][0-9]|3[01])$/', $actual);
   }

   public function extractOKProvider()
    {
        return [
            ['https://www.youtube.com/watch?v=BaW_jenozKc'],
            ['https://vimeo.com/293999425'],
            ['https://www.canal-u.tv/video/cinematheque_francaise/eloge_de_la_maladresse_pratique_obstinee_du_detour_macha_makeieff_et_jerome_deschamps.4741'],
            ['https://www.franceinter.fr/emissions/lettres-d-interieur/lettres-d-interieur-04-juin-2020']
            
        ];
    }
    /**
     * @dataProvider extractOKProvider
     */
   public function testExtractSingleOK($url){
        $ytdl = new Ytdl($this->options, $this->logger);
        $ytdl->setCache(['directory' => 'tests/cache']);
        $actual = $ytdl->extractInfos($url);
        $this->assertFileExists('tests/cache/' . hash('sha256', $url) . '.json');
        $this->assertEquals($url, $actual['webpage_url']);
        $this->assertArrayNotHasKey('_type', $actual);
        $this->assertNull($ytdl->getErrors());
   }

   public function extractNoOK(){
       return [
           ['https://lyl.live/episode/planete-noire-26'],
           ['https://symfony.com/doc/current/components/process.html#finding-the-executable-php-binary'],
           ['https://www.youtube.com/watch?v=kTn9Rl7cFOA']
       ];
   }
   /**
    * @dataProvider extractNoOK
    */
   public function testExtractNoOK($url){
        $ytdl = new Ytdl($this->options, $this->logger);
        $ytdl->setCache(['directory' => 'tests/cache']);
        $actual = $ytdl->extractInfos($url);
        $this->assertEmpty($actual);
        $this->assertFileDoesNotExist('tests/cache/' . hash('sha256', $url) . '.json');
        $this->assertNotNull($ytdl->getErrors());
   }

   public function extractPlaylistOKProvider(){
       return [
           ['https://soundcloud.com/lg2-3/sets/amiral-prose-monthly-radio'],
           ['https://www.telerama.fr/radio/2010-2020-une-decennie-de-radio-vue-par-sonia-devillers,n6577631.php'],
           ['https://www.youtube.com/playlist?list=PLmUCF8zaE6GtED3-SbN31InCGLUw0_9bU'],
           ['https://www.arte.tv/fr/videos/RC-017841/dopamine/']

       ];

   }
   /**
    * @dataProvider extractPlaylistOKProvider
    */
   public function testExtractPlaylistOK($url){
       $ytdl = new Ytdl($this->options, $this->logger);
       $ytdl->setCache(['directory' => 'tests/cache']);
       $actual = $ytdl->extractInfos($url);
       $this->assertFileExists('tests/cache/' . hash('sha256', $url) . '.json');
       $this->assertArrayHasKey('_type', $actual);
       $this->assertEquals($url, $actual['webpage_url']);
       $this->assertNull($ytdl->getErrors());
   }

   /*
   public function testDownload(){
    //    $this->options->addOptions(['-o' => 're1e-%(id)s.%(ext)s']);
       $ytdl = new Ytdl($this->options, $this->logger);
       $ytdl->setCache(['directory' => 'tests/cache']);

       $actual = $ytdl->download('https://www.youtube.com/watch?v=BaW_jenozKc');
    //    $actual = $ytdl->download('https://www.telerama.fr/radio/2010-2020-une-decennie-de-radio-vue-par-sonia-devillers,n6577631.php');
       file_put_contents('./aa.json', json_encode($actual));

    //    $this->assertEquals();

        //    $this->options->addOptions(['-o' => 're1e-%(id)s.%(ext)s']);
       $ytdl = new Ytdl($this->options, $this->logger);
       $ytdl->setCache(['directory' => 'tests/cache']);

    //    $actual = $ytdl->download('https://vimeo.com/293999425');
    //    $actual = $ytdl->download('https://www.youtube.com/watch?v=BaW_jenozKc');
       $actual = $ytdl->download('https://www.telerama.fr/radio/2010-2020-une-decennie-de-radio-vue-par-sonia-devillers,n6577631.php');
       file_put_contents('./aa.json', json_encode($actual));

    //    $this->assertEquals();
   }
   */


   



}
