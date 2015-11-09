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
use Symfony\Component\Console\Helper\ProgressBar;
use Chain\Chain;

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

        $backupPath = $this->destPath . '_backup';

        $progress = new ProgressBar($output, 9);
        $progress->setFormat(" <info>%message%</info>\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%");

        if (!$this->askDoubleConfirmation($output)) {
            return;
        }

        $output->writeln(' ');

        $progress->setMessage('Making some checks…');
        $progress->start();

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
        if ($this->sourcePath == $this->destPath) {
            throw new \Exception(sprintf('Source and destination paths ‘%s’ are the same', $this->destPath), 1);
        }
        if (!file_exists($this->sourcePath.'/files') || !file_exists($this->sourcePath.'/bin/roadiz')) {
            throw new \Exception(sprintf('Source path ‘%s’ is not a valid Roadiz repository.', $this->sourcePath), 1);
        }
        if (!file_exists($this->destPath.'/files') || !file_exists($this->destPath.'/bin/roadiz')) {
            throw new \Exception(sprintf('Destination path ‘%s’ is not a valid Roadiz repository.', $this->destPath), 1);
        }

        /*
         * Dump mysql database
         */
        $progress->setMessage('Dumping source database…');
        $progress->advance();
        $tmp_mysqldump = tempnam(sys_get_temp_dir(), $this->sourceDatabaseName . '_dump');
        if (false === $this->dumpDatabase($this->sourceDatabaseName, $tmp_mysqldump, $output)) {
            throw new \Exception(sprintf(
                'Impossible to dump ‘%s’ database to ‘%s’ file.',
                $this->sourceDatabaseName,
                $tmp_mysqldump
            ), 1);
        }

        /*
         * Backup destination database before overriding it.
         */
        if ($input->getOption('backup')) {
            $progress->setMessage('Backup destination database before overriding it…');
            $progress->advance();

            /*
             * Create backup folder if not exists
             */
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0775);
            }
            $tmp_mysqldump2 = sprintf("%s/%s_%s.sql", $backupPath, $this->destDatabaseName, date('YmdHis'));
            if (false === $this->dumpDatabase($this->destDatabaseName, $tmp_mysqldump2, $output)) {
                throw new \Exception(sprintf(
                    'Impossible to dump ‘%s’ database to ‘%s’ file.',
                    $this->destDatabaseName,
                    $tmp_mysqldump2
                ), 1);
            }
        } else {
            $progress->advance();
        }

        /*
         * Import dump into new database
         */
        $progress->setMessage('Importing dump file into destination database…');
        $progress->advance();
        if (false === $this->importDatabase($this->destDatabaseName, $tmp_mysqldump, $output)) {
            throw new \Exception(sprintf(
                'Impossible to import ‘%s’ database from ‘%s’ file.',
                $this->destDatabaseName,
                $tmp_mysqldump
            ), 1);
        }

        /*
         * Backup destination files before overriding them…
         */
        if ($input->getOption('backup')) {
            $progress->setMessage('Backup destination files before overriding them…');
            $progress->advance();

            /*
             * Create backup folder if not exists
             */
            $filesBackupPath = $backupPath . '/' . date('YmdHis');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0775);
            }
            if (!file_exists($filesBackupPath)) {
                mkdir($filesBackupPath, 0775);
                mkdir($filesBackupPath . '/files', 0775);
            }
            if (false === $this->rsyncFiles($this->destPath, $filesBackupPath, $output)) {
                throw new \Exception(sprintf(
                    'Impossible to sync ‘%s/files/’ to ‘%s/files/’.',
                    $this->destPath,
                    $this->filesBackupPath
                ), 1);
            }
        } else {
            $progress->advance();
        }

        /*
         * RSync Roadiz documents files…
         */
        $progress->setMessage('Syncing Roadiz document files…');
        $progress->advance();
        if (false === $this->rsyncFiles($this->sourcePath, $this->destPath, $output)) {
            throw new \Exception(sprintf(
                'Impossible to sync ‘%s/files/’ to ‘%s/files/’.',
                $this->sourcePath,
                $this->destPath
            ), 1);
        }

        /*
         * Regenerate nodeSources classes
         */
        $progress->setMessage('Regenerating NodesSources classes…');
        $progress->advance();
        if (false === $this->regenerateNodesSourcesClasses($this->destPath, $output)) {
            throw new \Exception(sprintf(
                'Impossible to regenerate NodesSources classes in ‘%s/gen-src/GeneratedNodeSources/’.',
                $this->destPath
            ), 1);
        }

        /*
         * Regenerate nodeSources classes
         */
        $progress->setMessage('Update database schema…');
        $progress->advance();
        if (false === $this->updateDatabaseSchema($this->destPath, $output)) {
            throw new \Exception(sprintf(
                'Impossible to update database schema in ‘%s’.',
                $this->destPath
            ), 1);
        }

        /*
         * Regenerate nodeSources classes
         */
        $progress->setMessage('Empty Roadiz caches…');
        $progress->advance();
        if (false === $this->emptyCacheSchema($this->destPath, $output)) {
            throw new \Exception(sprintf(
                'Impossible to empty caches in ‘%s’.',
                $this->destPath
            ), 1);
        }

        $progress->setMessage('Done!');
        $progress->finish();
    }

    protected function regenerateNodesSourcesClasses($destPath, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.php.path', $output));
        $builder->setWorkingDirectory($destPath);
        $arguments = [
            'bin/roadiz',
            'core:sources',
            '-r',
        ];

        $regenProcess = $builder->setArguments($arguments)->getProcess();
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln($regenProcess->getCommandLine());
        }
        $regenProcess->run();
        // executes after the command finishes
        return $regenProcess->isSuccessful();
    }

    protected function updateDatabaseSchema($destPath, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.php.path', $output));
        $builder->setWorkingDirectory($destPath);
        $arguments = [
            'bin/roadiz',
            'orm:schema-tool:update',
            '--dump-sql',
            '--force',
        ];

        $updateSchemaProcess = $builder->setArguments($arguments)->getProcess();
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln($updateSchemaProcess->getCommandLine());
        }
        $updateSchemaProcess->run();
        // executes after the command finishes
        return $updateSchemaProcess->isSuccessful();
    }

    protected function emptyCacheSchema($destPath, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.php.path', $output));
        $builder->setWorkingDirectory($destPath);
        $arguments = [
            'bin/roadiz',
            'cache',
            '-a',
            '--env=prod',
        ];

        $emptyCacheProcess = $builder->setArguments($arguments)->getProcess();
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln($emptyCacheProcess->getCommandLine());
        }
        $emptyCacheProcess->run();
        // executes after the command finishes
        return $emptyCacheProcess->isSuccessful();
    }

    protected function rsyncFiles($sourcePath, $destPath, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.rsync.path', $output));
        $arguments = [
            '-avc',
            '--delete',
            $sourcePath . '/files/',
            $destPath . '/files/',
        ];

        $rsyncProcess = $builder->setArguments($arguments)->getProcess();
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln($rsyncProcess->getCommandLine());
        }
        $rsyncProcess->run();
        // executes after the command finishes
        return $rsyncProcess->isSuccessful();
    }

    protected function dumpDatabase($databaseName, $outputFilePath, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.mysqldump.path', $output));
        $arguments = [
            '-h' . $this->get('db.host', $output),
            '-u' . $this->get('db.username', $output),
            '-p' . $this->get('db.password', $output),
        ];

        if ($this->get('db.port', $output) > 0) {
            $arguments[] = '--port=' . $this->get('db.port', $output);
        }
        $arguments[] = $databaseName;

        $databaseProcess = $builder->setArguments($arguments)->getProcess();

        $chain = new Chain($databaseProcess);
        $chain->add('>', $outputFilePath);

        $chainProcess = $chain->getProcess();
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln($chainProcess->getCommandLine());
        }
        $chainProcess->run();
        // executes after the command finishes
        return $chainProcess->isSuccessful();
    }

    protected function importDatabase($databaseName, $inputFilePath, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.mysql.path', $output));
        $arguments = [
            '-h' . $this->get('db.host', $output),
            '-u' . $this->get('db.username', $output),
            '-p' . $this->get('db.password', $output),
        ];

        if ($this->get('db.port', $output) > 0) {
            $arguments[] = '--port=' . $this->get('db.port', $output);
        }
        $arguments[] = $databaseName;

        $databaseProcess = $builder->setArguments($arguments)->getProcess();

        $chain = new Chain($databaseProcess);
        $chain->add('<', $inputFilePath);
        //$output->writeln($databaseProcess->getCommandLine());
        $chainProcess = $chain->getProcess();
        $chainProcess->run();
        // executes after the command finishes
        return $chainProcess->isSuccessful();
    }

    protected function checkDatabaseExistance($databaseName, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix($this->get('commands.mysql.path', $output));
        $arguments = [
            '-h' . $this->get('db.host', $output),
            '-u' . $this->get('db.username', $output),
            '-p' . $this->get('db.password', $output),
            '-e',
            'use ' . $databaseName,
        ];

        if ($this->get('db.port', $output) > 0) {
            $arguments[] = '--port=' . $this->get('db.port', $output);
        }

        $databaseProcess = $builder->setArguments($arguments)->getProcess();

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
You are going to move your Roadiz files from <info>"%s"</info> to <info>"%s"</info>.
Your database <info>"%s"</info> will be used to OVERRIDE <info>"%s"</info> database.
<question>Are these informations correct? (yes|no)</question> ',
                $this->sourcePath,
                $this->destPath,
                $this->sourceDatabaseName,
                $this->destDatabaseName
            ),
            false
        ) && $dialog->askConfirmation(
            $output,
            '<question>This operation cannot be undone, are you sure to continue? (yes|no)</question> ',
            false
        );
    }
}
