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
 * @file MoveDataCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\RoadizCliTools\Command;

use Symfony\Component\Console\Command\Command;
use RZ\RoadizCliTools\Command\ConfigurableCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Move data from one Roadiz instance to an other.
 */
class MoveDataCommand extends ConfigurableCommand
{
    protected $sourcePath;
    protected $sourceDatabaseName;
    protected $destPath;
    protected $destDatabaseName;

    protected function configure()
    {
        $this
            ->setName('roadiz:move')
            ->setDescription('Move data (files and database) from one Roadiz instance to an other')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('source', InputArgument::REQUIRED, 'Source Roadiz instance path'),
                    new InputArgument('destination', InputArgument::REQUIRED, 'Destination Roadiz instance path'),
                    new InputArgument('source-database', InputArgument::REQUIRED, 'Source Roadiz database name'),
                    new InputArgument('destination-database', InputArgument::REQUIRED, 'Destination Roadiz database name'),
                    new InputOption('backup', 'b', InputOption::VALUE_NONE, 'Backup the destination path and database before overriding it'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sourcePath = $input->getArgument('source');
        $this->sourceDatabaseName = $input->getArgument('source-database');
        $this->destPath = $input->getArgument('destination');
        $this->destDatabaseName = $input->getArgument('destination-database');

        if (!$this->askDoubleConfirmation($output)) {
            return;
        }

        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.mysql.path', $output));

        $testSrcDatabaseProcess = $builder->setArguments([
            '-h'.$this->get('db.host', $output),
            '-u'.$this->get('db.username', $output),
            '-p'.$this->get('db.password', $output),
            '-e',
            'use '.$this->sourceDatabaseName,
        ])->getProcess();
        $output->writeln($testSrcDatabaseProcess->getCommandLine());
        $testSrcDatabaseProcess->run();
        // executes after the command finishes
        if (!$testSrcDatabaseProcess->isSuccessful()) {
            throw new ProcessFailedException($testSrcDatabaseProcess);
        }

        $testDestDatabaseProcess = $builder->setArguments([
            '-h'.$this->get('db.host', $output),
            '-u'.$this->get('db.username', $output),
            '-p'.$this->get('db.password', $output),
            '-e',
            'use '.$this->destDatabaseName,
        ])->getProcess();
        $output->writeln($testDestDatabaseProcess->getCommandLine());
        $testDestDatabaseProcess->run();
        // executes after the command finishes
        if (!$testDestDatabaseProcess->isSuccessful()) {
            throw new ProcessFailedException($testDestDatabaseProcess);
        }
    }

    protected function askDoubleConfirmation(OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        return $dialog->askConfirmation(
            $output,
            sprintf(
                '
You are going to move your Roadiz files from "%s" to "%s".
Your database "%s" will be used to OVERRIDE "%s" database.
<question>Are these informations correct?</question>',
                $this->sourcePath,
                $this->destPath,
                $this->sourceDatabaseName,
                $this->destDatabaseName
            ),
            false
        ) && $dialog->askConfirmation(
            $output,
            '<question>Are you sure to continue?</question>',
            false
        );
    }
}
