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
 * @file RequirementsCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\RoadizCliTools\Command;

use RZ\RoadizCliTools\Command\ConfigurableCommand;
use RZ\RoadizCliTools\Requirements;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequirementsCommand extends ConfigurableCommand
{
    protected function configure()
    {
        $this->setName('roadiz:requirements')
            ->setDescription('Test available binaries to use roadiz-cli.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $binaries = [];
        $binaries[$this->get('commands.git.path', $output)] = $this->get('commands.git.test', $output);
        $binaries[$this->get('commands.mysqldump.path', $output)] = $this->get('commands.mysqldump.test', $output);
        $binaries[$this->get('commands.mysql.path', $output)] = $this->get('commands.mysql.test', $output);
        $binaries[$this->get('commands.cp.path', $output)] = $this->get('commands.cp.test', $output);
        $binaries[$this->get('commands.rsync.path', $output)] = $this->get('commands.rsync.test', $output);
        $binaries[$this->get('commands.composer.path', $output)] = $this->get('commands.composer.test', $output);

        $requirements = new Requirements($binaries);
        $final = $requirements->testBinaries($output);

        if ($final) {
            $output->writeln('<info>All requirements passed</info>');
        } else {
            $output->writeln('<error>Requirements failed</error>');
        }
    }
}
