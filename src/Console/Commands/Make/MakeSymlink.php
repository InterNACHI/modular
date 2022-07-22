<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\ModuleRegistry;
use Illuminate\Support\Str;

class MakeSymlink extends
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "modules:inertia-link {module}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create symbolic link to root asset/js/Pages directory for given module";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ModuleRegistry $registry)
    {
        $module = $registry->module($this->argument("module"));

        $name = $this->argument("module");

        if ($module) {
            if (file_exists(resource_path("js/Pages/" . ucwords($name)))) {
                return $this->error(
                    "The resources/js/Pages/" .
                        ucfirst(Str::camel($name)) .
                        " already exist."
                );
            }

            if (
                is_link(resource_path("js/Pages/" . ucfirst(Str::camel($name))))
            ) {
                $this->laravel
                    ->make("files")
                    ->delete(
                        resource_path("js/Pages/" . ucfirst(Str::camel($name)))
                    );
            }

            if (
                !file_exists(
                    base_path("app-modules/" . $name . "/resources/js")
                )
            ) {
                return $this->error("No such file or directory");
            }

            $this->laravel
                ->make("files")
                ->relativeLink(
                    base_path("app-modules/" . $name . "/resources/js"),
                    resource_path("js/Pages/" . ucfirst(Str::camel($name)))
                );

            $this->info(
                "The[" .
                    base_path("app-modules/" . $name . "/resources/js") .
                    "] link has been connected to [" .
                    resource_path("js/Pages/" . ucfirst(Str::camel($name))) .
                    "]."
            );
        } else {
            $this->error(
                "Module '" .
                    $this->argument("module") .
                    "' not found. Make sure to link only module that exist in app-modules directory."
            );
        }
        return;
    }
}
