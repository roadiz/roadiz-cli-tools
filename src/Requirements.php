<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file Requirements.php
 * @author Ambroise Maupate
 */
namespace RZ\RoadizCliTools;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Requirements
{
    protected $binaries;

    public function __construct(array $binaries = [])
    {
        $this->binaries = $binaries;
    }

    protected function isCommandAvailable($string, OutputInterface $output)
    {
        $process = new Process($string);
        $process->run();
        // executes after the command finishes
        return $process->isSuccessful() && $process->getOutput() != "";
    }

    public function testBinaries(OutputInterface $output)
    {
        $testPassed = true;

        foreach ($this->binaries as $binary => $command) {
            $test = $this->isCommandAvailable($command, $output);
            $output->writeln(sprintf('<info>%s</info> => %s', $binary, $test ? 'OK' : '<error>FAIL</error>'));

            if ($test === false) {
                $testPassed = false;
            }
        }

        return $testPassed;
    }
}
