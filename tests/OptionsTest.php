<?php

use Flatgreen\Ytdl\Options;
use PHPUnit\Framework\TestCase;

final class OptionsTest extends TestCase{

    private $options_tests = ['--write-auto-subs', '-f' => '18', '--test'];
    private $options_output = ['-o' => 'data/something.ext'];
    private $options_tests_linear = ['--write-auto-subs', '-f', '18', '--test'];
    private $options_tests_linear_plus_output = ['--write-auto-subs', '-f', '18', '--test', '-o', 'data/something.ext'];

    public function testCreate(){
        $opt = new Options();
        $this->assertInstanceOf(Options::class, $opt);
    }

    public function testAddOptionsException(){
        $opt = new Options;
        $this->expectException(LogicException::class);
        $opt->addOptions(['-f', '18']);
    }

    public function testAddOptions(){
        $opt = new Options;
        $opt->addOptions($this->options_tests);
        $opt->addOptions($this->options_output);
        $actual = $opt->getOptions();
        $this->assertIsArray($actual);
        $expected = array_merge($opt->getDefaultOptions(), $this->options_tests_linear_plus_output);
        $this->assertEquals(count($expected), count($actual));
        foreach($actual as $k => $v){
            $this->assertEquals($expected[$k], $v);
        }
    }

    public function testAddRawOptions(){
        $opt = new Options;
        $cmd_line = '--one_alone --with_value value --second_alone -t';
        $cmd_line_to_linear_options = ['--one_alone', '--with_value', 'value', '--second_alone', '-t'];

        $opt->addRawOptions($cmd_line);
        $actual = $opt->getOptions();
        $this->assertIsArray($actual);
        $expected = array_merge($opt->getDefaultOptions(), $cmd_line_to_linear_options);
        $this->assertEquals(count($expected), count($actual));
        foreach($actual as $k => $v){
            $this->assertEquals($expected[$k], $v);
        }
    }

    public function testIsOption(){
        $opt = new Options;
        $opt->addOptions($this->options_tests);
        $this->assertTrue($opt->isOption('--write-auto-subs'));
        $this->assertTrue($opt->isOption('-f'));
        $this->assertFalse($opt->isOption('18'));
        $this->assertFalse($opt->isOption('--output'));
    }

    public function testGetOption(){
        $opt = new Options;
        $opt->addOptions($this->options_tests);
        $this->assertSame('18', $opt->getOption('-f'), 'option with value');
        $this->assertSame('--write-auto-subs', $opt->getOption('--write-auto-subs'), 'option -- alone');
        $this->assertEquals('', $opt->getOption('--a'), 'option empty');
        $this->assertSame('default', $opt->getOption('--a', 'default'), 'not an option return default');
    }

    public function testDeleteOption(){
        $opt = new Options;
        $opt->addOptions($this->options_tests);
        $opt->removeOption('-f');
        $expected = array_merge($opt->getDefaultOptions(), ['--write-auto-subs', '--test']);
        $this->assertEquals($expected, $opt->getOptions());
        $opt->removeOption('--write-auto-subs');
        $expected2 = array_merge($opt->getDefaultOptions(), ['--test']);
        $this->assertEquals($expected2, $opt->getOptions());
    }
}