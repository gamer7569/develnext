<?php
namespace ide\bundle;

use ide\Ide;
use ide\Logger;
use ide\project\behaviours\GradleProjectBehaviour;
use ide\project\behaviours\PhpProjectBehaviour;
use ide\project\Project;
use ide\utils\FileUtils;
use php\compress\ArchiveEntry;
use php\compress\ArchiveInputStream;
use php\io\File;
use php\io\IOException;
use php\io\Stream;
use php\lib\arr;
use php\lib\fs;
use php\lib\str;
use php\net\URL;
use php\util\Regex;
use php\util\Scanner;

/**
 * Class AbstractJarBundle
 * @package ide\bundle
 */
abstract class AbstractJarBundle extends AbstractBundle
{
    /**
     * @return array
     */
    function getJarDependencies()
    {
        return [];
    }

    /**
     * @return string
     */
    function getDescription()
    {
        return $this->getName() . " JAR Library";
    }

    public function onAdd(Project $project, AbstractBundle $owner = null)
    {
        parent::onAdd($project, $owner);

        $libPath = $project->getFile('lib/');

        foreach ($this->getJarDependencies() as $dep) {
            if (!is_array($dep)) {
                $jarFile = $this->findLibFile($dep);

                if ($jarFile) {
                    $file = "$libPath/$dep.jar";

                    if (fs::isFile($jarFile)) {
                        $size1 = fs::size($jarFile);
                        $size2 = fs::size($file);

                        if ($size1 != $size2 || !fs::isFile($file)) {
                            if (FileUtils::copyFile($jarFile, $file) < 0) {
                                Logger::error("Unable to copy $jarFile to $file");
                            }
                        }

                    } else {
                        Logger::error("Unable to copy $jarFile");
                    }

                    $project->loadSourceForInspector($file);
                    $php = PhpProjectBehaviour::get();

                    if ($php) {
                        $php->addExternalJarLibrary($file);
                    }
                }
            }
        }

        if (fs::isDir($this->bundleDirectory)) {
            FileUtils::copyDirectory($this->bundleDirectory, "$libPath/{$this->getVendorName()}");

            $php = PhpProjectBehaviour::get();

            fs::scan($this->bundleDirectory, function ($filename) use ($php, $project) {
                if (fs::ext($filename) == 'jar' && fs::nameNoExt($filename) != fs::name($this->bundleDirectory)) {
                    $project->loadSourceForInspector($filename);

                    /*if ($php) {
                        $php->addExternalJarLibrary($filename);
                    }  */
                }
            });
        }
    }

    public function onRemove(Project $project, AbstractBundle $owner = null)
    {
        parent::onRemove($project, $owner);

        $libPath = $project->getFile('lib/');

        if (fs::isDir("$libPath/{$this->getVendorName()}")) {
            $php = PhpProjectBehaviour::get();

            fs::scan($this->bundleDirectory, function ($filename) use ($php, $project) {
                if (fs::ext($filename) == 'jar' && fs::nameNoExt($filename) != fs::name($this->bundleDirectory)) {
                    $project->unloadSourceForInspector($filename);

                    /*if ($php) {
                        $php->addExternalJarLibrary($filename);
                    }*/
                }
            });
        }
    }


    /**
     * @param Project $project
     * @param string $env
     * @param callable|null $log
     */
    public function onPreCompile(Project $project, $env, callable $log = null)
    {
        parent::onPreCompile($project, $env, $log);

        // todo remove it!
    }

    /**
     * @param GradleProjectBehaviour $gradle
     */
    public function applyForGradle(GradleProjectBehaviour $gradle)
    {
        foreach ($this->getJarDependencies() as $dep) {
            if (is_array($dep)) {
                $gradle->addDependency($dep[1], $dep[0], $dep[2]);
            } else {
                $gradle->addDependency($dep);
            }
        }

        if ($this->bundleDirectory) {
            $gradle->addDependency("dir:lib/{$this->getVendorName()}");
        }
    }

    protected function getSearchLibPaths()
    {
        return [
            Ide::get()->getOwnFile('lib/')
        ];
    }

    private function findLibFile($name)
    {
        /** @var File[] $libPaths */
        $libPaths = $this->getSearchLibPaths();

        if (Ide::get()->isDevelopment()) {
            $ownFile = Ide::get()->getOwnFile('build/install/develnext/lib');
            $libPaths[] = $ownFile;
        }

        $regex = Regex::of('(\.[0-9]+|\-[0-9]+)');

        $name = $regex->with($name)->replace('');

        foreach ($libPaths as $libPath) {
            foreach ($libPath->findFiles() as $file) {
                $filename = $regex->with($file->getName())->replace('');

                if (str::endsWith($filename, '.jar') || str::endsWith($filename, '-SNAPSHOT.jar')) {
                    $filename = str::sub($filename, 0, Str::length($filename) - 4);

                    if (str::endsWith($filename, '-SNAPSHOT')) {
                        $filename = Str::sub($filename, 0, Str::length($filename) - 9);
                    }

                    if ($filename == $name) {
                        return $file;
                    }
                }
            }
        }

        return null;
    }
}