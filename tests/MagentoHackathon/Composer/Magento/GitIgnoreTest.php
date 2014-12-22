<?php

namespace MagentoHackathon\Composer\Magento;

use org\bovigo\vfs\vfsStream;

/**
 * Class GitIgnoreTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnoreTest extends \PHPUnit_Framework_TestCase
{
    protected $gitIgnoreFile;

    public function setUp()
    {
        vfsStream::setup('root');
        $this->gitIgnoreFile = vfsStream::url('root/.gitignore');
    }

    public function testIfFileNotExistsItIsCreated()
    {
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $gitIgnore->addEntry("file1");
        $gitIgnore->write();
        $this->assertFileExists($this->gitIgnoreFile);
    }

    public function testIfFileExistsExistingLinesAreLoaded()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $this->assertFileExists($this->gitIgnoreFile);
        $this->assertSame($lines, $gitIgnore->getEntries());
    }

    public function testAddEntryDoesNotAddDuplicates()
    {
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $gitIgnore->addEntry("file1.txt");
        $gitIgnore->addEntry("file1.txt");
        $this->assertCount(1, $gitIgnore->getEntries());
    }

    public function testGitIgnoreIsNotWrittenIfNoAdditions()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));
        $writeTime = filemtime($this->gitIgnoreFile);
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $gitIgnore->write();
        clearstatcache();
        $this->assertEquals($writeTime, filemtime($this->gitIgnoreFile));
    }

    public function testCanRemoveEntry()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $gitIgnore->removeEntry('line1');
        $this->assertEquals(array('line2'), $gitIgnore->getEntries());
    }

    public function testCanAddMultipleEntries()
    {
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $gitIgnore->addMultipleEntries(array('file1.txt', 'file2.txt'));
        $this->assertSame(array('file1.txt', 'file2.txt'), $gitIgnore->getEntries());
    }

    public function testCanRemoveMultipleEntries()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));
        $gitIgnore = new GitIgnore($this->gitIgnoreFile);
        $gitIgnore->removeMultipleEntries(array('line1', 'line2'));
        $this->assertSame(array(), $gitIgnore->getEntries());
    }
}
