<?php

use Flatgreen\Ytdl\Options;
use PHPUnit\Framework\TestCase;

// TODO voir pour le dossier 'data' (in 'test' ?)

final class OptionsTest extends TestCase{

    private $options_tests = ['--ignore-errors', '-f' => '18', '--test'];
    private $options_tests_linear = ['--ignore-errors', '-f', '18', '--test'];
    private $options_output = ['-o' => 'data/something.ext'];

    public function testCreate(){
        $opt = new Options();
        $this->assertInstanceOf(Options::class, $opt);
    }

    public function testSetOptions(){
        $opt = new Options;
        $opt->setOptions($this->options_tests);

        $actual = $opt->getOptions();
        $this->assertIsArray($actual);
        $this->assertEqualsCanonicalizing($this->options_tests_linear, $actual);
    }

    public function testSetOptionsException(){
        $opt = new Options;
        $this->expectException(LogicException::class);
        $opt->setOptions(['-f', '18']);
    }

    public function testAddOptions(){
        $opt = new Options;
        $opt->setOptions($this->options_tests);
        $opt->addOptions($this->options_output);
        $actual = $opt->getOptions();
        $this->assertIsArray($actual);
        $this->assertEqualsCanonicalizing(['--ignore-errors', '-f' , '18', '--test', '-o', 'data/something.ext'], $actual);
    }

    public function testIsOption(){
        $opt = new Options;
        $opt->setOptions($this->options_tests);
        $this->assertTrue($opt->isOption('-f'));
        $this->assertFalse($opt->isOption('18'));
        $this->assertFalse($opt->isOption('--output'));
    }

    public function testGetOption(){
        $opt = new Options;
        $opt->setOptions($this->options_tests);
        $this->assertSame('18', $opt->getOption('-f'));
        $this->assertSame('--ignore-errors', $opt->getOption('--ignore-errors'));
        $this->assertNull($opt->getOption('--a'));
        $this->assertSame('default', $opt->getOption('--a', 'default'));
    }

    public function testDeleteOption(){
        $opt = new Options;
        $opt->setOptions($this->options_tests);
        $opt->removeOption('-f');
        $this->assertEqualsCanonicalizing(['--ignore-errors', '--test'], $opt->getOptions());
        $opt->setOptions($this->options_tests);
        $opt->removeOption('--test');
        $this->assertEqualsCanonicalizing(['--ignore-errors', '-f', '18'],      $opt->getOptions());
    }
}