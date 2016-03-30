<?php
/*
 * Copyright 2015 Bastian Schwarz <bastian@codename-php.de>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @namespace
 */
namespace de\codenamephp\prototype\library\platform;

use \Composer\Script\Event;
use \Twig_Environment;
use \Twig_Loader_Filesystem;

/**
 *
 * @author Bastian Schwarz <bastian@codename-php.de>
 */
class Installer {

  public static function install(Event $event) {
    $filesystem = new \Symfony\Component\Filesystem\Filesystem();

    $homeDir = realpath($event->getComposer()->getConfig()->get('vendor-dir') . '/..');

    $namespace = $event->getIO()->ask("What's the base namespace? ");
    $displayName = $event->getIO()->ask('What is the display name of the project? ');
    $componentName = $event->getIO()->ask(sprintf('What is the name of the project? [%s] ', basename($homeDir)), basename($homeDir));

    $namespacedFolder = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

    $srcFolder = $homeDir . '/src/main/php/' . $namespacedFolder;
    $testFolder = $homeDir . '/src/test/php/' . $namespacedFolder;

    umask(0);
    if(!file_exists($srcFolder)) {
      $filesystem->mkdir($srcFolder, 0755);
    }
    if(!file_exists($testFolder)) {
      $filesystem->mkdir($testFolder, 0755);
    }

    if($filesystem->exists($homeDir . '/.git')) {
      $filesystem->remove($homeDir . '/.git');
    }
    if($filesystem->exists($homeDir . '/README.md')) {
      $filesystem->remove($homeDir . '/README.md');
    }
    if($filesystem->exists($homeDir . '/src/main/php/README.md')) {
      $filesystem->remove($homeDir . '/src/main/php/README.md');
    }

    $loader = new Twig_Loader_Filesystem($homeDir . '/installer/templates');
    $twig = new Twig_Environment($loader);

    file_put_contents($srcFolder . '/Dummy.php', $twig->render('Dummy.php', array('namespace' => $namespace)));
    file_put_contents($testFolder . '/TestCase.php', $twig->render('TestCase.php', array('namespace' => $namespace)));
    file_put_contents($homeDir . '/nbproject/project.xml', $twig->render('project.xml', array('displayName' => $displayName)));
    file_put_contents($homeDir . '/build.xml', $twig->render('build.xml', array('displayName' => $displayName)));
    file_put_contents($homeDir . '/README.md', $twig->render('README.md', array('componentName' => $componentName)));

    if($event->getIO()->askConfirmation('Replace composer.json and remove installer?[Y/n] ', true)) {
      file_put_contents($homeDir . '/composer.json', $twig->render('composer.json', array('componentName' => $componentName)));
      $filesystem->remove($homeDir . '/installer');
    }
  }
}
