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
 * @file ConfigurableCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\RoadizCliTools\Command;

use RZ\RoadizCliTools\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class ConfigurableCommand extends Command
{
    protected $processedConfiguration = null;

    protected function getConfiguration()
    {
        $configDefault = Yaml::parse(
            file_get_contents(APP_ROOT . '/config.default.yml')
        );
        $configs = [$configDefault];

        if (file_exists(APP_ROOT . '/config.yml')) {
            $configUser = Yaml::parse(
                file_get_contents(APP_ROOT . '/config.yml')
            );
            $configs[] = $configUser;
        }
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration(
            $configuration,
            $configs
        );
    }

    /**
     *
     * @param  string          $path
     * @param  OutputInterface $output
     * @return mixed
     */
    public function get($path, OutputInterface $output)
    {
        if (null === $this->processedConfiguration) {
            $this->processedConfiguration = $this->getConfiguration();
        }

        return $this->getValueAtPath($this->processedConfiguration, $path);
    }

    private function getValueAtPath($array, $path)
    {
        $pathArray = explode('.', $path);

        $temp = &$array;
        foreach ($pathArray as $key) {
            if (isset($temp[$key])) {
                $temp = &$temp[$key];
            } else {
                throw new \Exception(sprintf("Configuration value for key ‘%s’ does not exist.", $path), 1);
            }
        }
        return $temp;
    }
}
