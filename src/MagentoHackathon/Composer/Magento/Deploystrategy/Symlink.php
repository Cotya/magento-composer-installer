<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Symlink deploy strategy
 */
class Symlink extends DeploystrategyAbstract
{
    /**
     * Creates a symlink with lots of error-checking
     *
     * @param string $source
     * @param string $dest
     * @return bool
     * @throws \ErrorException
     */
    public function createDelegate($source, $dest)
    {
        $sourcePath = $this->getSourceDir() . '/' . $this->removeTrailingSlash($source);
        $destPath = $this->getDestDir() . '/' . $this->removeTrailingSlash($dest);

        if (!is_file($sourcePath) && !is_dir($sourcePath)) {
            throw new \ErrorException("Could not find path '$sourcePath'");
        }

        /*

        Assume app/etc exists, app/etc/a does not exist unless specified differently

        OK dir app/etc/a  --> link app/etc/a to dir
        OK dir app/etc/   --> link app/etc/dir to dir
        OK dir app/etc    --> link app/etc/dir to dir

        OK dir/* app/etc     --> for each dir/$file create a target link in app/etc
        OK dir/* app/etc/    --> for each dir/$file create a target link in app/etc
        OK dir/* app/etc/a   --> for each dir/$file create a target link in app/etc/a
        OK dir/* app/etc/a/  --> for each dir/$file create a target link in app/etc/a

        OK file app/etc    --> link app/etc/file to file
        OK file app/etc/   --> link app/etc/file to file
        OK file app/etc/a  --> link app/etc/a to file
        OK file app/etc/a  --> if app/etc/a is a file throw exception unless force is set, in that case rm and see above
        OK file app/etc/a/ --> link app/etc/a/file to file regardless if app/etc/a existst or not

        */

        // Symlink already exists
        if (is_link($destPath)) {
            if (realpath(readlink($destPath)) == realpath($sourcePath)) {
                // .. and is equal to current source-link
                return true;
            }
            unlink($destPath);
        }

        // Create all directories up to one below the target if they don't exist
        $destDir = dirname($destPath);
        if (!file_exists($destDir)) {
            mkdir($destDir, 0777, true);
        }

        // Handle source to dir linking,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        if (file_exists($destPath) && is_dir($destPath)){
            $newDest = $destPath . '/' . basename($source);
            return $this->create($source, substr($newDest, strlen($this->getDestDir())+1));
        }

        // From now on $destPath can't be a directory, that case is already handled

        // If file exists and force is not specified, throw exception unless FORCE is set
        // existing symlinks are already handled
        if (file_exists($destPath)) {
            if ($this->isForced()) {
                unlink($destPath);
            } else {
                throw new \ErrorException("Target $dest already exists and is not a symlink");
            }
        }

        // Windows doesn't allow relative symlinks
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $sourcePath = $this->getRelativePath($destPath, $sourcePath);
        }

        // Create symlink
        if(false === symlink($sourcePath, $destPath)) {
            throw new \ErrorException("An error occured while creating symlink" . $sourcePath);
        }

        // Check we where able to create the symlink
        if(false === $destPath = readlink($destPath)){
            throw new \ErrorException("Symlink $destPath points to target $destPath");
        }

        return true;
    }

    /**
     * Returns the relative path from $from to $to
     *
     * This is utility method for symlink creation.
     * Orig Source: http://stackoverflow.com/a/2638272/485589
     */
    public function getRelativePath($from, $to)
    {
        // Can't use realpath() here since the destination doesn't exist yet
        $from = str_replace(array('/./', '//'), '/', $from);
        $from = explode('/', $from);

        $to = str_replace(array('/./', '//'), '/', $to);
        $to = explode('/', $to);

        $relPath = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }
}
