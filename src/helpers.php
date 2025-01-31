<?php 

if (! function_exists('modules_path')) {
    /**
     * Get the path to the modules folder.
     *
     * @param  string  $path
     * @return string
     */
    function modules_path($path = '')
    {
        $directory_name = config('app-modules.modules_directory', 'app-modules');
        $path = base_path($directory_name . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
        return str_replace('\\', '/', rtrim($path, '/\\'));
    }
}