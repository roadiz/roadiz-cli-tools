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

use RZ\RoadizCliTools\Command\ConfigurableCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

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

        if (!$this->checkDatabaseExistance($this->sourceDatabaseName, $output)) {
            throw new \Exception(sprintf('Source database ‘%s’ does not exist', $this->sourceDatabaseName), 1);
        }
        if (!$this->checkDatabaseExistance($this->destDatabaseName, $output)) {
            throw new \Exception(sprintf('Destination database ‘%s’ does not exist', $this->destDatabaseName), 1);
        }
        if (!file_exists($this->sourcePath)) {
            throw new \Exception(sprintf('Source path ‘%s’ does not exist', $this->sourcePath), 1);
        }
        if (!file_exists($this->destPath)) {
            throw new \Exception(sprintf('Destination path ‘%s’ does not exist', $this->destPath), 1);
        }
        if (!file_exists($this->sourcePath.'/files') || !file_exists($this->sourcePath.'/bin/roadiz')) {
            throw new \Exception(sprintf('Source path ‘%s’ is not a valid Roadiz repository.', $this->sourcePath), 1);
        }
        if (!file_exists($this->destPath.'/files') || !file_exists($this->destPath.'/bin/roadiz')) {
            throw new \Exception(sprintf('Destination path ‘%s’ is not a valid Roadiz repository.', $this->destPath), 1);
        }
    }

    protected function checkDatabaseExistance($databaseName, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.mysql.path', $output));

        $databaseProcess = $builder->setArguments([
            '-h' . $this->get('db.host', $output),
            '-u' . $this->get('db.username', $output),
            '-p' . $this->get('db.password', $output),
            '-e',
            'use ' . $databaseName,
        ])->getProcess();
        $databaseProcess->run();
        // executes after the command finishes
        return $databaseProcess->isSuccessful();
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
