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
        $this->logger = new ColorLogger();
        $this->options = new Options();
    }


    public function testCreate()
    {
        $opt = new Options();
        $ytdl = new Ytdl($opt);
        $this->assertInstanceOf(Ytdl::class, $ytdl);
    }

    public function testGetExecYtdlName()
    {
        $ytdl = new Ytdl($this->options);
        $this->assertContains($ytdl->getYtdlExecName(), ['yt-dlp', 'youtube-dl']);
    }

    public function testRun()
    {
        $this->options->addOptions(['--version']);
        $ytdl = new Ytdl($this->options, $this->logger);
        $actual = $ytdl->run();
        // it's a date !
        $this->assertMatchesRegularExpression('/^(19|20)\d\d\.(0[1-9]|1[012])\.(0[1-9]|[12][0-9]|3[01])$/', $actual);
    }

    public function extractOKProvider()
    {
        return [
            ['DTi8wZ1a1TA', 'https://www.youtube.com/watch?v=DTi8wZ1a1TA'],
            ['24da2291-56fe-42e9-9a87-0f327be87619', 'https://www.france.tv/spectacles-et-culture/theatre-et-danse/les-trois-mousquetaires-la-serie/6807847-episode-12-la-chasse-a-l-equipement-ou-il-s-agit-de-s-equiper.html']
        ];
    }
    /**
    * @dataProvider extractOKProvider
    */
    public function testExtractSingleOk($id, $url)
    {
        $ytdl = new Ytdl($this->options, $this->logger);
        $actual = $ytdl->extractInfos($url);
        $this->assertEquals($id, $actual['id']);
        $this->assertEmpty($ytdl->getErrors(), 'no error');
    }

    public function extractNoOk()
    {
        return [
            ['https://lyl.live/episode/planete-noire-26'],
            ['https://symfony.com/doc/current/components/process.html#finding-the-executable-php-binary'],
            ['https://www.youtube.com/watch?v=kTn9Rl7cFOA']
        ];
    }
    /**
    * @dataProvider extractNoOK
    */
    public function testExtractNoOk($url)
    {
        $ytdl = new Ytdl($this->options, $this->logger);
        $actual = $ytdl->extractInfos($url);
        $this->assertEmpty($actual);
        $this->assertNotEmpty($ytdl->getErrors());
    }

    public function extractPlaylistOkProvider()
    {
        return [
            ['https://soundcloud.com/lg2-3/sets/amiral-prose-monthly-radio'],
            ['https://www.youtube.com/playlist?list=PLm5uVy7nNXqiA3Ykbj9pAouApqBOUCYHd']
        ];
    }
    /**
    * @dataProvider extractPlaylistOKProvider
    */
    public function testExtractPlaylistOk($url)
    {
        $ytdl = new Ytdl($this->options, $this->logger);
        $actual = $ytdl->extractInfos($url);
        $this->assertArrayHasKey('_type', $actual);
        $this->assertArrayHasKey('entries', $actual);
        $this->assertEquals('playlist', $actual['_type']);
        $this->assertTrue($ytdl->isPlaylist($actual));
        $this->assertEmpty($ytdl->getErrors(), 'no error');
    }

}
